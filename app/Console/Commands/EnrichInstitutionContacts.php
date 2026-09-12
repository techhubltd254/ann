<?php

namespace App\Console\Commands;

use App\Models\CountyInstitution;
use App\Models\CountyHotel;
use App\Models\CountyHealthFacility;
use App\Models\CountyTourismAttraction;
use Illuminate\Console\Command;

class EnrichInstitutionContacts extends Command
{
    protected $signature = 'institutions:enrich-contacts
        {--dry-run : Only show what would be updated, do not save}
        {--limit=50 : Max institutions to process}
        {--source=google : Data source (google)}';

    protected $description = 'Scrape the web for missing institution contact details (phone, email, website)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $source = $this->option('source');

        $this->info("Institution contact enrichment (source: {$source})");
        if ($dryRun) $this->warn('DRY RUN — no changes will be saved');

        $enriched = 0;
        $failed = 0;

        // Collect all institution-type models missing contact fields
        $models = [];

        CountyInstitution::whereNull('phone')->orWhereNull('email')->orWhereNull('website')
            ->orWhere('phone', '')->orWhere('email', '')->orWhere('website', '')
            ->chunk(50, function ($items) use (&$models) {
                foreach ($items as $item) $models[] = $item;
            });

        $this->info('Found ' . count($models) . ' institutions needing enrichment');
        $models = array_slice($models, 0, $limit);

        $bar = $this->output->createProgressBar(count($models));
        $bar->start();

        foreach ($models as $institution) {
            try {
                $contacts = $this->searchContacts($institution->name, $institution->county?->name ?? '');

                if ($contacts) {
                    $changed = false;
                    if (!$institution->phone && !empty($contacts['phone'])) {
                        $institution->phone = $contacts['phone'];
                        $changed = true;
                        $this->line("  <info>phone:</info> {$contacts['phone']}");
                    }
                    if (!$institution->email && !empty($contacts['email'])) {
                        $institution->email = $contacts['email'];
                        $changed = true;
                        $this->line("  <info>email:</info> {$contacts['email']}");
                    }
                    if (!$institution->website && !empty($contacts['website'])) {
                        $institution->website = $contacts['website'];
                        $changed = true;
                        $this->line("  <info>website:</info> {$contacts['website']}");
                    }
                    if ($changed && !$dryRun) {
                        $institution->save();
                        $enriched++;
                        $this->line("  <fg=green>✓ {$institution->name}</>");
                    } elseif ($changed) {
                        $enriched++;
                        $this->line("  <fg=yellow>✓ {$institution->name} (dry run)</>");
                    }
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->line("  <error>✗ {$institution->name}: {$e->getMessage()}</error>");
            }

            $bar->advance();
            // Be polite to the source — 1 second between requests
            if (!$dryRun) sleep(1);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done: {$enriched} enriched, {$failed} failed");

        return Command::SUCCESS;
    }

    /**
     * Search for contact details for a given institution name + location.
     * Uses a web search approach to find phone, email, and website.
     */
    protected function searchContacts(string $name, string $county): ?array
    {
        $query = urlencode("{$name} {$county} Kenya contact phone email website");
        $searchUrl = "https://www.google.com/search?q={$query}";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (compatible; KICCEnrichBot/1.0; +https://kicctest.org)\r\n",
                'timeout' => 10,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $html = @file_get_contents($searchUrl, false, $context);
        if (!$html) return null;

        $contacts = [];

        // Extract phone numbers (Kenyan: +254... or 0...)
        if (preg_match_all('/(?:\+254|0)[17]\d{8}/', $html, $m)) {
            $contacts['phone'] = $m[0][0];
        }

        // Extract email addresses
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $html, $m)) {
            // Filter out common non-institution emails
            $filtered = array_filter($m[0], fn($e) => !str_contains($e, 'example.com') && !str_contains($e, 'google.com'));
            if (!empty($filtered)) {
                $contacts['email'] = array_values($filtered)[0];
            }
        }

        // Extract website URLs
        if (preg_match_all('/https?:\/\/(?:www\.)?[a-zA-Z0-9-]+\.[a-zA-Z]{2,}(?:\/[^\s"<]*)?/', $html, $m)) {
            $filtered = array_filter($m[0], fn($u) =>
                !str_contains($u, 'google.com') &&
                !str_contains($u, 'youtube.com') &&
                !str_contains($u, 'facebook.com') &&
                !str_contains($u, 'twitter.com') &&
                !str_contains($u, 'instagram.com')
            );
            if (!empty($filtered)) {
                $contacts['website'] = array_values($filtered)[0];
            }
        }

        return !empty($contacts) ? $contacts : null;
    }
}