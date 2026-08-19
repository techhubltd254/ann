<?php

namespace Tests\Unit;

use App\Services\ElasticsearchService;
use App\Services\AdService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceLayerTest extends TestCase
{
    public function test_elasticsearch_graceful_degradation(): void
    {
        $es = new ElasticsearchService();
        $this->assertFalse($es->available());
        $this->assertEmpty($es->search('test'));
        $this->assertFalse($es->index('test', '1', ['name' => 'test']));
        $this->assertFalse($es->delete('test', '1'));
    }

    public function test_ad_service_graceful_degradation(): void
    {
        if (! Schema::hasTable('ad_placements')) {
            $this->markTestSkipped('ad_placements not available');
        }
        $this->assertNull(AdService::serve('nonexistent_placement'));
        $this->assertNull(AdService::click(999999));
    }

    public function test_config_has_required_keys(): void
    {
        $this->assertIsArray(config('ads.placements'));
        $this->assertArrayHasKey('featured_placement', config('ads.placements'));
        $this->assertArrayHasKey('county_comarketing', config('ads.placements'));
        $this->assertArrayHasKey('referral', config('ads.placements'));
        $this->assertArrayHasKey('livestream_banner', config('ads.placements'));
    }

    public function test_ad_pricing_is_configured(): void
    {
        $placements = config('ads.placements');
        foreach ($placements as $key => $placement) {
            $this->assertArrayHasKey('price_monthly', $placement, "$key missing price_monthly");
            $this->assertArrayHasKey('price_usd', $placement, "$key missing price_usd");
            $this->assertIsNumeric($placement['price_monthly']);
            $this->assertIsNumeric($placement['price_usd']);
        }
    }

    public function test_subscription_tiers_are_configured(): void
    {
        $tiers = config('ads.subscriptions');
        $this->assertArrayHasKey('basic', $tiers);
        $this->assertArrayHasKey('premium', $tiers);
        $this->assertArrayHasKey('enterprise', $tiers);
    }

    public function test_commission_rate_is_configured(): void
    {
        $rate = config('ads.commission_rate');
        $this->assertIsNumeric($rate);
        $this->assertGreaterThan(0, $rate);
        $this->assertLessThan(1, $rate);
    }

    public function test_government_services_are_configured(): void
    {
        $gov = config('services.government');
        $this->assertIsArray($gov);
        $this->assertArrayHasKey('kra', $gov);
        $this->assertArrayHasKey('huduma', $gov);
        $this->assertArrayHasKey('ntsa', $gov);
        $this->assertArrayHasKey('kws', $gov);
        $this->assertArrayHasKey('education', $gov);
        $this->assertArrayHasKey('tourism', $gov);
        $this->assertArrayHasKey('agriculture', $gov);
        $this->assertArrayHasKey('health', $gov);
        $this->assertArrayHasKey('museums', $gov);
    }

    public function test_openapi_spec_exists(): void
    {
        $this->assertFileExists(base_path('docs/openapi.yaml'));
    }

    public function test_sentry_config_exists(): void
    {
        $this->assertFileExists(config_path('sentry.php'));
        $this->assertIsArray(config('sentry'));
    }

    public function test_horizon_config_exists(): void
    {
        $this->assertFileExists(config_path('horizon.php'));
        $this->assertIsArray(config('horizon'));
    }

    public function test_pipeline_config_exists(): void
    {
        $this->assertFileExists(config_path('pipeline.php'));
        $this->assertIsArray(config('pipeline'));
    }
}