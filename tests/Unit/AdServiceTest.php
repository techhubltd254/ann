<?php

namespace Tests\Unit;

use App\Services\AdService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdServiceTest extends TestCase
{
    public function test_serve_returns_null_for_invalid_placement(): void
    {
        if (! Schema::hasTable('ad_placements')) {
            $this->markTestSkipped('ad_placements table not available');
        }
        $result = AdService::serve('nonexistent_placement');
        $this->assertNull($result);
    }

    public function test_click_returns_null_for_invalid_creative(): void
    {
        if (! Schema::hasTable('ad_creatives')) {
            $this->markTestSkipped('ad_creatives table not available');
        }
        $result = AdService::click(999999);
        $this->assertNull($result);
    }
}