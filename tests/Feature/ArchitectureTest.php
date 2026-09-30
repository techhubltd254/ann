<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * @group architecture
 *
 * Architecture regression tests — enforced module boundaries.
 * These are the safety net: a failing test here means a developer
 * bypassed a module boundary or introduced a naming collision.
 */
class ArchitectureTest extends TestCase
{
    /**
     * No controller may call DB::table() directly. All data access
     * must go through Eloquent models or a dedicated repository.
     *
     * Known exceptions: webhook controllers (Stripe/M-Pesa) handle
     * third-party payloads; admin controllers with DB::table() are
     * tracked and must be resolved in compartmentalization Phase 3c.
     */
    public function test_no_controller_uses_db_table_directly(): void
    {
        $controllers = glob(app_path('Http/Controllers/**/*.php'));
        $violations = [];
        $knownExceptions = [
            // Webhook handlers — third-party payload validation
            'StripeWebhookController', 'MpesaWebhookController',
            'CourierWebhookController', 'VerifyN8nWebhook',
            // Tracked: Phase 3c decomposition (god controllers)
            'KiccAdminController', 'CountyAdminController',
            // Tracked: DB::table() elimination backlog
            'EcommerceAdminController', 'AttractionBookingController',
            'DbCheckController', 'DisputeController', 'ExperiencePricingController',
            'InstitutionAdminController', 'IntelligenceController',
            'LivestreamController', 'NationalAdminController',
            'PipelineController', 'PipelineLicenceController',
            'ProviderPortalController', 'TravelController',
            'AuthController', 'MarketplaceApiController',
            'SemanticSearchController',
        ];

        foreach ($controllers as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            if (in_array($className, $knownExceptions, true)) continue;

            $content = file_get_contents($file);
            // Count DB::table() calls (not in comments)
            preg_match_all('/DB\s*::\s*table\s*\(/', $content, $matches);
            $count = count($matches[0] ?? []);

            // Count DB::raw() calls
            preg_match_all('/DB\s*::\s*raw\s*\(/', $content, $rawMatches);
            $rawCount = count($rawMatches[0] ?? []);

            if ($count > 0 || $rawCount > 0) {
                $violations[] = str_replace(app_path() . '/', '', $file)
                    . " DB::table:{$count} DB::raw:{$rawCount}";
            }
        }

        $this->assertEmpty($violations,
            "Controllers must not use DB::table() or DB::raw() directly:\n" .
            implode("\n", $violations)
        );
    }

    /**
     * No service class name collision across namespaces.
     * Two classes with the same short name in different namespaces
     * but accessible from the same DI container is a bug.
     */
    public function test_no_duplicate_service_class_names(): void
    {
        $serviceFiles = glob(app_path('Services/**/*.php'));
        $names = [];

        foreach ($serviceFiles as $file) {
            $short = pathinfo($file, PATHINFO_FILENAME);
            if (! isset($names[$short])) $names[$short] = [];
            $names[$short][] = str_replace(app_path() . '/', '', $file);
        }

        $duplicates = [];
        foreach ($names as $name => $files) {
            if (count($files) > 1) {
                $duplicates[$name] = $files;
            }
        }

        // Known acceptable duplicates — these serve different purposes
        // across namespaces and are intentional bounded-context collocations.
        $knownAcceptable = [
            'LedgerService',     // App\Kicc\Services (lifecycle) vs App\Services\JournalService (renamed)
            'PoolEngine',        // App\Services\PoolEngine (root) vs App\Services\Pool\PoolEngine (sub-context)
            'CountyClassificationService', // App\Services (AlgorithmsClient) only — Kicc variant deleted
        ];

        $duplicates = array_diff_key($duplicates, array_flip($knownAcceptable));

        $this->assertEmpty($duplicates,
            "Duplicate service class names found (file collision risk):\n" .
            json_encode($duplicates, JSON_PRETTY_PRINT)
        );
    }

    /**
     * PipelineResolver must NOT exist — PipelineRouter is the canonical router.
     */
    public function test_pipeline_resolver_is_deleted(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Services/PipelineResolver.php'),
            'PipelineResolver is stale. Use PipelineRouter instead.'
        );
    }

    /**
     * JournalService (renamed from LedgerService) must exist.
     * The old static LedgerService at App\Services was renamed to avoid
     * collision with App\Kicc\Services\LedgerService (lifecycle ledger).
     */
    public function test_journal_service_exists(): void
    {
        $this->assertFileExists(
            app_path('Services/JournalService.php'),
            'JournalService (static journal) must exist.'
        );
        $content = file_get_contents(app_path('Services/JournalService.php'));
        $this->assertStringContainsString('class JournalService', $content);
    }
}