<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\County;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UssdCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function ussdPost(string $text, string $token = ''): \Illuminate\Testing\TestResponse
    {
        return $this->post('/api/ussd/callback', [
            'sessionId' => 'ATXyz123',
            'serviceCode' => '*384*1234#',
            'phoneNumber' => '254700000000',
            'text' => $text,
            'token' => $token,
        ]);
    }

    public function test_invalid_token_rejected(): void
    {
        config()->set('services.africastalking.ussd_callback_token', 'secret-token');

        $this->ussdPost('', 'wrong-token')->assertStatus(401);
    }

    public function test_valid_token_accepted(): void
    {
        config()->set('services.africastalking.ussd_callback_token', 'secret-token');

        $this->ussdPost('', 'secret-token')
            ->assertOk()
            ->assertSee('CON KICC Exhibition Platform')
            ->assertSee('County info');
    }

    public function test_main_menu_without_token_when_not_configured(): void
    {
        config()->set('services.africastalking.ussd_callback_token', '');

        $this->ussdPost('')
            ->assertOk()
            ->assertSee('CON KICC Exhibition Platform');
    }

    public function test_next_exhibition(): void
    {
        County::create([
            'name' => 'Nairobi City',
            'capital' => 'Nairobi',
            'code' => 'KE-030',
            'former_province' => 'Nairobi',
            'economic_zone' => 'Metropolitan',
            'population_2024' => 4500000,
            'area_km2' => 696,
            'is_active' => true,
        ]);

        $exhibition = \App\Models\Exhibition::create([
            'name' => 'KICC Trade Expo 2026',
            'slug' => 'kicc-trade-expo-2026',
            'description' => 'National trade expo',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'status' => 'published',
            'is_featured' => true,
        ]);

        $this->ussdPost('1')
            ->assertOk()
            ->assertSee('END Next: KICC Trade Expo 2026');
    }

    public function test_no_upcoming_exhibition(): void
    {
        $this->ussdPost('1')
            ->assertOk()
            ->assertSee('END No upcoming exhibition');
    }

    public function test_bookings_menu_and_detail(): void
    {
        $user = User::factory()->create(['phone' => '254700000000']);

        $exhibition = \App\Models\Exhibition::create([
            'name' => 'KICC Trade Expo 2026',
            'slug' => 'kicc-trade-expo-2026',
            'description' => 'National trade expo',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'status' => 'published',
            'is_featured' => true,
        ]);

        $booking = Booking::create([
            'booking_reference' => 'KICC-BK-TEST001',
            'user_id' => $user->id,
            'exhibition_id' => $exhibition->id,
            'booking_type' => 'exhibition',
            'subtotal' => 1000,
            'tax' => 160,
            'total' => 1160,
            'currency' => 'KES',
            'status' => 'confirmed',
            'paid_at' => now(),
        ]);

        $this->ussdPost('2')
            ->assertOk()
            ->assertSee('CON Select a booking')
            ->assertSee('KICC-BK-TEST001');

        $this->ussdPost('2*1')
            ->assertOk()
            ->assertSee('END KICC-BK-TEST001')
            ->assertSee('Status: confirmed')
            ->assertSee('KES 1,160');
    }

    public function test_no_bookings(): void
    {
        $this->ussdPost('2')
            ->assertOk()
            ->assertSee('END No bookings found');
    }

    public function test_county_info_lookup(): void
    {
        County::create([
            'name' => 'Mombasa',
            'capital' => 'Mombasa City',
            'code' => 'KE-001',
            'former_province' => 'Coast',
            'economic_zone' => 'Coastal Strip',
            'population_2024' => 1260000,
            'area_km2' => 229.7,
            'tourism_highlights' => ['Fort Jesus', 'Diani Beach', 'Old Town'],
            'is_active' => true,
        ]);

        $this->ussdPost('3*Mombasa')
            ->assertOk()
            ->assertSee('END Mombasa County')
            ->assertSee('Population: 1,260,000')
            ->assertSee('Fort Jesus');
    }

    public function test_unknown_county(): void
    {
        $this->ussdPost('3*Atlantis')
            ->assertOk()
            ->assertSee('END County not found');
    }

    public function test_trade_inquiry_flow(): void
    {
        $this->ussdPost('4*coffee')->assertOk()->assertSee('CON Send details to our trade desk');

        $this->ussdPost('4*coffee*1')
            ->assertOk()
            ->assertSee('END Trade inquiry received for: coffee');
    }

    public function test_exit_option(): void
    {
        $this->ussdPost('0')->assertOk()->assertSee('END Thank you');
    }

    public function test_invalid_selection(): void
    {
        $this->ussdPost('9')->assertOk()->assertSee('CON Invalid selection');
    }
}