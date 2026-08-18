<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\County;
use App\Models\Exhibition;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Africa's Talking USSD menu engine.
 *
 * AT POSTs form fields (sessionId, serviceCode, phoneNumber, text) to the
 * configured callback URL. `text` accumulates the dialled digits joined by
 * '*' (e.g. "1*2"). Respond with `CON <menu>` to continue the session or
 * `END <message>` to terminate.
 */
class UssdService
{
    public function __invoke(string $phone, string $text): string
    {
        $steps = array_values(array_filter(explode('*', $text), fn (string $s) => $s !== ''));

        try {
            return match (count($steps)) {
                0       => $this->mainMenu(),
                1       => $this->handleStep1($steps[0], $phone),
                2       => $this->handleStep2($steps[0], $steps[1], $phone),
                default => $this->handleStep3($steps[0], $steps[1], $steps[2]),
            };
        } catch (\Throwable $e) {
            Log::error('USSD error: ' . $e->getMessage(), ['phone' => $phone, 'text' => $text]);
            return 'END Sorry, something went wrong. Please try again later.';
        }
    }

    protected function mainMenu(): string
    {
        return 'CON KICC Exhibition Platform' . PHP_EOL
            . '1. Next exhibition' . PHP_EOL
            . '2. My bookings' . PHP_EOL
            . '3. County info' . PHP_EOL
            . '4. Trade with KICC' . PHP_EOL
            . '0. Exit';
    }

    protected function handleStep1(string $choice, string $phone): string
    {
        return match ($choice) {
            '1'       => $this->nextExhibition(),
            '2'       => $this->bookingsMenu($phone),
            '3'       => 'CON Enter county name (e.g. Nairobi):',
            '4'       => 'CON Enter product/service you want to trade (e.g. coffee, tours):',
            '0', ''   => 'END Thank you for using KICC. Goodbye.',
            default   => $this->invalid('mainMenu'),
        };
    }

    protected function handleStep2(string $step1, string $step2, string $phone): string
    {
        return match ($step1) {
            '2'       => $this->bookingDetail($phone, $step2),
            '3'       => $this->countyInfo($step2),
            '4'       => $this->tradeOptions($step2),
            default   => $this->invalid('mainMenu'),
        };
    }

    protected function handleStep3(string $step1, string $step2, string $step3): string
    {
        if ($step1 === '4') {
            return $this->tradeConfirm($step2, $step3);
        }

        return $this->invalid('mainMenu');
    }

    protected function nextExhibition(): string
    {
        $ex = Exhibition::query()
            ->where('status', 'published')
            ->whereDate('start_date', '>=', today())
            ->orderBy('start_date')
            ->first();

        if (! $ex) {
            return 'END No upcoming exhibition is scheduled yet. Check back soon.';
        }

        $venue = $ex->venue?->name ?? 'TBD';
        $when = $ex->start_date?->format('d M Y') . ' to ' . $ex->end_date?->format('d M Y');

        return 'END Next: ' . $ex->name . PHP_EOL
            . 'Venue: ' . $venue . PHP_EOL
            . 'Dates: ' . $when . PHP_EOL
            . 'More info at kicctest.org';
    }

    protected function bookingsMenu(string $phone): string
    {
        $bookings = $this->bookingsFor($phone);

        if ($bookings->isEmpty()) {
            return 'END No bookings found for ' . $this->maskPhone($phone) . '.';
        }

        $lines = ['CON Select a booking:'];
        foreach ($bookings->take(5)->values() as $i => $b) {
            $lines[] = ($i + 1) . '. ' . $b->booking_reference . ' (' . $b->status . ')';
        }

        return implode(PHP_EOL, $lines);
    }

    protected function bookingDetail(string $phone, string $index): string
    {
        $bookings = $this->bookingsFor($phone);
        $booking = $bookings->values()->get((int) $index - 1);

        if (! $booking) {
            return 'END Booking not found. Try again.';
        }

        return 'END ' . $booking->booking_reference . PHP_EOL
            . 'Type: ' . ($booking->booking_type ?? 'N/A') . PHP_EOL
            . 'Total: KES ' . number_format((float) $booking->total, 0) . PHP_EOL
            . 'Status: ' . $booking->status . PHP_EOL
            . 'Paid: ' . ($booking->paid_at?->format('d M Y H:i') ?? 'No');
    }

    protected function countyInfo(string $name): string
    {
        $county = County::query()
            ->where('name', 'like', '%' . trim($name) . '%')
            ->orWhere('slug', Str::slug($name))
            ->first();

        if (! $county) {
            return 'END County not found. Check the spelling and try again.';
        }

        $attractions = collect($county->tourism_highlights ?? [])->take(3)->implode(', ');

        return 'END ' . $county->name . ' County' . PHP_EOL
            . 'Province: ' . ($county->former_province ?? 'N/A') . PHP_EOL
            . 'Population: ' . number_format((float) ($county->population_2024 ?? 0)) . PHP_EOL
            . 'Top sights: ' . ($attractions ?: 'N/A');
    }

    protected function tradeOptions(string $product): string
    {
        return 'CON Send details to our trade desk: ' . PHP_EOL
            . '1. Yes, submit inquiry' . PHP_EOL
            . '2. Cancel';
    }

    protected function tradeConfirm(string $product, string $choice): string
    {
        if ($choice !== '1') {
            return 'END Inquiry cancelled.';
        }

        Log::info('USSD trade inquiry', ['product' => $product]);

        return 'END Trade inquiry received for: ' . $product . PHP_EOL
            . 'Our trade desk will contact you shortly.';
    }

    protected function invalid(string $menu): string
    {
        Log::warning('USSD invalid input', ['menu' => $menu]);

        return 'CON Invalid selection. Please choose a valid option or 0 to exit.';
    }

    protected function bookingsFor(string $phone): \Illuminate\Support\Collection
    {
        $user = User::query()->where('phone', $phone)->orWhere('phone', $this->normalizePhone($phone))->first();

        if (! $user) {
            return collect();
        }

        return $user->bookings()->latest()->get();
    }

    protected function maskPhone(string $phone): string
    {
        return strlen($phone) >= 4 ? '****' . substr($phone, -4) : $phone;
    }

    protected function normalizePhone(string $phone): string
    {
        return Str::startsWith($phone, '0') ? '254' . substr($phone, 1) : $phone;
    }
}
