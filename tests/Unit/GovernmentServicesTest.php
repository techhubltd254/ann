<?php

namespace Tests\Unit;

use App\Services\Government\NtsaService;
use App\Services\Government\KwService;
use App\Services\Government\EducationService;
use App\Services\Government\TourismService;
use App\Services\Government\AgricultureService;
use App\Services\Government\HealthService;
use App\Services\Government\MuseumsService;
use Tests\TestCase;

class GovernmentServicesTest extends TestCase
{
    public function test_ntsa_returns_null_without_api_key(): void
    {
        $service = new NtsaService();
        $this->assertNull($service->verifyVehicle('KCA 001A'));
        $this->assertNull($service->verifyDrivingLicense('DL12345'));
    }

    public function test_kws_returns_null_without_api_key(): void
    {
        $service = new KwService();
        $this->assertNull($service->getPark('tsavo'));
        $this->assertIsArray($service->listParks() ?? []);
    }

    public function test_education_returns_null_without_api_key(): void
    {
        $service = new EducationService();
        $this->assertNull($service->verifyInstitution('SCH001'));
        $this->assertIsArray($service->listInstitutions() ?? []);
    }

    public function test_tourism_returns_null_without_api_key(): void
    {
        $service = new TourismService();
        $this->assertNull($service->getStarRating('HOTEL001'));
    }

    public function test_agriculture_returns_null_without_api_key(): void
    {
        $service = new AgricultureService();
        $this->assertNull($service->getCropPrices('maize'));
        $this->assertIsArray($service->getMarketData() ?? []);
    }

    public function test_health_returns_null_without_api_key(): void
    {
        $service = new HealthService();
        $this->assertNull($service->verifyFacility('FAC001'));
    }

    public function test_museums_returns_null_without_api_key(): void
    {
        $service = new MuseumsService();
        $this->assertNull($service->getHeritageSite('fort-jesus'));
        $this->assertIsArray($service->listSites() ?? []);
    }
}