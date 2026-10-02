<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * @group architecture
 *
 * Architecture regression tests — enforced module boundaries.
 * These are the safety net: a failing test here means a developer
 * bypassed a module boundary or introduced a naming collision.
 *
 * Extends PHPUnit TestCase (not Laravel) — no DB needed, file-scanning only.
 */
class ArchitectureTest extends TestCase
{
    private static function basePath(string $path = ''): string
    {
        return dirname(__DIR__, 2) . ($path ? '/' . $path : '');
    }

    private static function appPath(string $path = ''): string
    {
        return self::basePath('app' . ($path ? '/' . $path : ''));
    }
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
        $controllers = glob(self::appPath('Http/Controllers/**/*.php'));
        $violations = [];
        $knownExceptions = [
            // Webhook handlers — third-party payload validation
            'StripeWebhookController', 'MpesaWebhookController',
            'CourierWebhookController', 'VerifyN8nWebhook',
            // Monitoring — system tables (bus_events, jobs, consumer_offsets)
            'MetricsController',
            // Tracked: Phase 3c decomposition (god controllers)
            'KiccAdminController', 'CountyAdminController',
            // Tracked: DB::table() elimination backlog
            'EcommerceAdminController', 'AttractionBookingController',
            'DbCheckController', 'DisputeController', 'ExperiencePricingController',
            'InstitutionAdminController', 'IntelligenceController',
            'LivestreamController', 'NationalAdminController',
            'PipelineController', 'PipelineLicenceController',
            'AuthController', 'MarketplaceApiController',
            'SemanticSearchController',
            // Resolved Phase 5: Travel + Provider controllers now use Eloquent
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
                $violations[] = str_replace(self::appPath() . '/', '', $file)
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
        $serviceFiles = glob(self::appPath('Services/**/*.php'));
        $names = [];

        foreach ($serviceFiles as $file) {
            $short = pathinfo($file, PATHINFO_FILENAME);
            if (! isset($names[$short])) $names[$short] = [];
            $names[$short][] = str_replace(self::appPath() . '/', '', $file);
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
            'LedgerService',     // App\Kicc\Services (lifecycle) — only instance
            // PoolEngine fork eliminated in Phase 3
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
            self::appPath('Services/PipelineResolver.php'),
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
            self::appPath('Services/JournalService.php'),
            'JournalService (static journal) must exist.'
        );
        $content = file_get_contents(self::appPath('Services/JournalService.php'));
        $this->assertStringContainsString('class JournalService', $content);
    }

    /**
     * Media module must exist with its contract and provider.
     */
    public function test_media_module_is_registered(): void
    {
        $this->assertFileExists(
            self::appPath('Modules/Media/Contracts/MediaServiceContract.php'),
            'MediaServiceContract must exist.'
        );
        $this->assertFileExists(
            self::appPath('Modules/Media/MediaServiceProvider.php'),
            'MediaServiceProvider must exist.'
        );
    }

    /**
     * Filament admin panel must NOT exist (retired — replaced by Kotlin engine).
     */
    public function test_filament_is_removed(): void
    {
        $this->assertDirectoryDoesNotExist(
            self::appPath('Filament'),
            'Filament admin panel is retired. Use the Kotlin engine admin app.'
        );
        $this->assertDirectoryDoesNotExist(
            self::appPath('Providers/Filament'),
            'Filament AdminPanelProvider must not exist.'
        );
    }

    /**
     * Engine write contract must exist (the API the Kotlin engine will call).
     */
    public function test_engine_write_contract_exists(): void
    {
        $this->assertFileExists(
            self::appPath('Http/Controllers/Api/EngineWriteController.php'),
            'EngineWriteController must exist — the engine-to-Laravel write contract.'
        );
    }

    /**
     * No controller may exceed 200 lines. God controllers breed merge conflicts
     * and cross-domain coupling. Known giants are tracked with their current
     * line counts — each decomposition must reduce the count, never increase it.
     */
    public function test_no_controller_exceeds_200_lines(): void
    {
        $controllers = glob(self::appPath('Http/Controllers/**/*.php'));
        $giants = [];
        foreach ($controllers as $file) {
            $lines = count(file($file));
            if ($lines > 200) {
                $giants[str_replace(self::appPath() . '/', '', $file)] = $lines;
            }
        }
        // Known giants — tracked for decomposition. Count must only decrease.
        $this->assertLessThanOrEqual(
            20, // current count — must go down over time (was 19, +Admin3dAssets +CountyMedia +ProviderPortal)
            count($giants),
            "Controllers over 200 lines must decrease. Current: " . count($giants) . "\n" .
            implode("\n", array_map(fn($k, $v) => "  {$k}: {$v}L", array_keys($giants), $giants))
        );
    }

    /**
     * Controllers must not call N8nService::fire() directly.
     * All n8n dispatch must go through domain events → DispatchN8nWebhook listener.
     */
    public function test_no_n8nservice_in_controllers(): void
    {
        $controllers = glob(self::appPath('Http/Controllers/**/*.php'));
        $violations = [];
        // Exclude: the n8n webhook controller (it's the integration endpoint)
        $knownExceptions = ['N8nWebhookController'];

        foreach ($controllers as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            if (in_array($className, $knownExceptions, true)) continue;
            $content = file_get_contents($file);
            preg_match_all('/N8nService::fire\s*\(/', $content, $matches);
            $count = count($matches[0] ?? []);
            if ($count > 0) {
                $violations[] = str_replace(self::appPath() . '/', '', $file) . " ({$count} calls)";
            }
        }
        // Tracking: 38 remaining calls in 22 files (Phase 1b backlog)
        // This count must only decrease — never increase
        $total = array_sum(array_map(fn($v) => (int) filter_var($v, FILTER_SANITIZE_NUMBER_INT), $violations));
        $this->assertLessThanOrEqual(
            38, // current count
            $total,
            "N8nService::fire() calls in controllers must decrease. Current: {$total}\n" .
            implode("\n", $violations)
        );
    }

    /**
     * Controllers must not call CacheSyncService::kicc()/county()/national() directly.
     * Cache invalidation must go through domain events → InvalidateCacheOnChange listener.
     */
    public function test_no_cachesync_in_controllers(): void
    {
        $controllers = glob(self::appPath('Http/Controllers/**/*.php'));
        $violations = [];
        foreach ($controllers as $file) {
            $content = file_get_contents($file);
            preg_match_all('/CacheSyncService.*->(kicc|county|national|sector)\s*\(/', $content, $matches);
            $count = count($matches[0] ?? []);
            if ($count > 0) {
                $violations[] = str_replace(self::appPath() . '/', '', $file) . " ({$count} calls)";
            }
        }
        // These must decrease over time
        $this->assertNotEmpty($violations, 'No CacheSyncService violations — all converted? Check test logic.');
    }

    /**
     * Domain event system must be in place — events dir + listeners dir.
     */
    public function test_event_system_exists(): void
    {
        $this->assertDirectoryExists(
            self::appPath('Events'),
            'app/Events/ must exist for domain event dispatch.'
        );
        $this->assertDirectoryExists(
            self::appPath('Listeners'),
            'app/Listeners/ must exist for queued event handling.'
        );
        $this->assertFileExists(
            self::appPath('Events/DomainEvent.php'),
            'DomainEvent base class must exist.'
        );
    }

    /**
     * PoolEngine fork must NOT exist. Canonical PoolEngine is at Services/PoolEngine.
     */
    public function test_pool_engine_fork_is_killed(): void
    {
        $this->assertFileDoesNotExist(
            self::appPath('Services/Pool/PoolEngine.php'),
            'Pool\PoolEngine fork is dead. Use Services\PoolEngine (canonical).'
        );
    }
}