<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VoiceSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_voice_search_endpoint_validates_audio_file(): void
    {
        $response = $this->postJson('/api/search/voice', []);
        $response->assertStatus(422);
    }

    public function test_voice_search_endpoint_rejects_wrong_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);
        $response = $this->postJson('/api/search/voice', ['audio' => $file]);
        $response->assertStatus(422);
    }

    public function test_voice_search_endpoint_rejects_large_file(): void
    {
        $file = UploadedFile::fake()->create('audio.wav', 6000);
        $response = $this->postJson('/api/search/voice', ['audio' => $file]);
        $response->assertStatus(422);
    }

    public function test_voice_search_returns_transcribed_text(): void
    {
        // Mock the OpenRouter API call
        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Mombasa county tourism']],
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->create('audio.wav', 100);
        $response = $this->postJson('/api/search/voice', ['audio' => $file]);

        $response->assertOk()
            ->assertJsonPath('text', 'Mombasa county tourism')
            ->assertJsonPath('query', 'Mombasa county tourism');
    }

    public function test_voice_search_microphone_button_appears_in_search_inputs(): void
    {
        // Render a component with data-voice-search and check the button exists
        $html = view('components.county-strip', ['counties' => collect()])->render();
        $this->assertStringContainsString('data-voice-search', $html);
    }
}