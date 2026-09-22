<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Pool\PoolEngine;
use App\Services\Pool\QualityScorer;
use App\Services\Pool\SponsorshipService;
use App\Services\Onboarding\KybService;
use App\Services\Onboarding\ScreeningService;
use App\Services\CountyClassificationService;
use App\Services\Mcp\KAnonymizerService;

class PoolIntegrationTest extends TestCase
{
    public function test_pool_engine_returns_array(): void
    {
        $engine = new PoolEngine();
        $result = $engine->distribute(999, '2026-09');
        $this->assertIsArray($result);
    }

    public function test_quality_scorer_returns_valid_score(): void
    {
        $scorer = new QualityScorer();
        $result = $scorer->score(
            deliveryRate: 0.95,
            adverseRate: 0.02,
            trustGrade: 'A',
            completeness: 0.8,
            avgReview: 4.5,
            mediaTier: 2
        );
        $this->assertArrayHasKey('score', $result);
        $this->assertGreaterThan(0, $result['score']);
        $this->assertArrayHasKey('components', $result);
        $this->assertCount(6, $result['components']);
    }

    public function test_quality_scorer_handles_minimal_input(): void
    {
        $scorer = new QualityScorer();
        $result = $scorer->score(
            deliveryRate: 0.0,
            adverseRate: 1.0,
            trustGrade: 'F',
            completeness: 0.0,
            avgReview: 0.0,
            mediaTier: 0
        );
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(1, $result['score']);
    }

    public function test_kyb_evaluates_tier_0(): void
    {
        $kyb = new KybService();
        $result = $kyb->evaluate(phoneVerified: false);
        $this->assertEquals(0, $result['tier']);
        $this->assertTrue($result['approved']);
    }

    public function test_kyb_evaluates_tier_1(): void
    {
        $kyb = new KybService();
        $result = $kyb->evaluate(
            phoneVerified: true,
            kraPin: 'A123456789Z',
            pinValidated: true,
            assetVerified: false
        );
        $this->assertEquals(1, $result['tier']);
        $this->assertTrue($result['approved']);
    }

    public function test_kyb_evaluates_tier_2(): void
    {
        $kyb = new KybService();
        $result = $kyb->evaluate(
            phoneVerified: true,
            kraPin: 'A123456789Z',
            pinValidated: true,
            assetVerified: true
        );
        $this->assertEquals(2, $result['tier']);
        $this->assertTrue($result['approved']);
    }

    public function test_kyb_rejects_invalid_pin(): void
    {
        $kyb = new KybService();
        $result = $kyb->evaluate(
            phoneVerified: true,
            kraPin: 'invalid',
            pinValidated: false
        );
        $this->assertFalse($result['approved']);
    }

    public function test_screening_clears_innocent_name(): void
    {
        $screener = new ScreeningService();
        $result = $screener->screen('John Kamau Mwangi');
        $this->assertFalse($result['hit']);
        $this->assertEquals('clear', $result['action']);
    }

    public function test_screening_detects_sanctions_match(): void
    {
        $screener = new ScreeningService();
        $result = $screener->screen('John Appropriator');
        $this->assertTrue($result['hit']);
        $this->assertEquals('freeze_pending_review', $result['action']);
    }

    public function test_jaro_winkler_identical(): void
    {
        $svc = new ScreeningService();
        $refl = new \ReflectionMethod($svc, 'jaroWinkler');
        $refl->setAccessible(true);
        $this->assertEqualsWithDelta(1.0, $refl->invoke($svc, 'test', 'test'), 0.001);
    }

    public function test_jaro_winkler_no_match(): void
    {
        $svc = new ScreeningService();
        $refl = new \ReflectionMethod($svc, 'jaroWinkler');
        $refl->setAccessible(true);
        $score = $refl->invoke($svc, 'abc', 'xyz');
        $this->assertEqualsWithDelta(0.0, $score, 0.001);
    }

    public function test_sponsorship_referral_fee(): void
    {
        $svc = new SponsorshipService();
        $fee = $svc->referralFee(2, 1000.00);
        $this->assertEquals(20.00, $fee);
    }

    public function test_county_classifier_math(): void
    {
        $svc = new CountyClassificationService();
        $reflRps = new \ReflectionMethod($svc, 'rps');
        $reflRps->setAccessible(true);
        $reflFns = new \ReflectionMethod($svc, 'fns');
        $reflFns->setAccessible(true);

        $county = new \App\Models\County(['name' => 'Test']);
        $county->gmv = 100; $county->water = 0.1; $county->roads = 0.1;
        $county->tourism_bookings = 0; $county->agri_exports = 0;
        $county->sez_pipeline = 0; $county->procurement_flow = 0;
        $county->health = 0; $county->power = 0;
        $county->security = 0; $county->education = 0;

        $rps = $reflRps->invoke($svc, $county);
        $fns = $reflFns->invoke($svc, $county);

        $this->assertGreaterThanOrEqual(0, $rps);
        $this->assertLessThanOrEqual(1, $fns);
    }

    public function test_anonymizer_suppresses_small_groups(): void
    {
        $svc = new KAnonymizerService();
        $svc->setK(5);
        $data = array_map(fn($i) => ['county_id' => $i], [1, 1, 2, 2, 2, 2, 2]);
        $result = $svc->anonymize($data);
        $this->assertCount(5, $result);
        $this->assertEquals(2, $result[0]['county_id']);
    }

    public function test_anonymizer_pass_through_when_k_is_1(): void
    {
        $svc = new KAnonymizerService();
        $svc->setK(1);
        $data = [['county_id' => 1], ['county_id' => 1]];
        $result = $svc->anonymize($data);
        $this->assertCount(2, $result);
    }

    public function test_screening_normalize(): void
    {
        $svc = new ScreeningService();
        $refl = new \ReflectionMethod($svc, 'normalize');
        $refl->setAccessible(true);
        $result = $refl->invoke($svc, '  John   Doe! ');
        $this->assertEquals('john doe', $result);
    }
}