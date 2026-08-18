<?php

namespace Tests\Feature;

use App\Services\ElasticsearchService;
use Tests\TestCase;

class ElasticsearchServiceTest extends TestCase
{
    public function test_service_returns_not_available_without_host(): void
    {
        $service = new ElasticsearchService();
        $this->assertFalse($service->available());
    }

    public function test_search_returns_empty_without_es(): void
    {
        $service = new ElasticsearchService();
        $results = $service->search('test query');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function test_index_returns_false_without_es(): void
    {
        $service = new ElasticsearchService();
        $result = $service->index('test_index', '1', ['name' => 'test']);
        $this->assertFalse($result);
    }

    public function test_bulk_index_returns_false_without_es(): void
    {
        $service = new ElasticsearchService();
        $result = $service->bulkIndex('test_index', ['1' => ['name' => 'test']]);
        $this->assertFalse($result);
    }

    public function test_delete_returns_false_without_es(): void
    {
        $service = new ElasticsearchService();
        $result = $service->delete('test_index', '1');
        $this->assertFalse($result);
    }

    public function test_create_index_returns_false_without_es(): void
    {
        $service = new ElasticsearchService();
        $result = $service->createIndex('test', ['properties' => ['name' => ['type' => 'text']]]);
        $this->assertFalse($result);
    }

    public function test_ensure_indices_does_not_throw_without_es(): void
    {
        $service = new ElasticsearchService();
        $service->ensureIndices();
        $this->assertTrue(true);
    }
}