<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Screen;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScreenController extends Controller
{
    /** Screen ad-rate packages (KES). */
    public const AD_PACKAGES = [
        'day'   => ['label' => '1 Day',   'price' => 2500,  'days' => 1],
        'week'  => ['label' => '1 Week',  'price' => 15000, 'days' => 7],
        'month' => ['label' => '1 Month', 'price' => 50000, 'days' => 30],
    ];

    public function index()
    {
        $screens = Screen::where('active', true)->orderBy('id')->get()->map(function ($screen) {
            $videoPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
            $screen->video_exists = file_exists($videoPath);
            $screen->video_size_mb = $screen->video_exists
                ? round(filesize($videoPath) / 1024 / 1024, 1)
                : null;
            $screen->immersive_url = $this->immersiveUrl($screen);
            return $screen;
        });

        return view('screens.index', compact('screens'));
    }

    public function show(string $id)
    {
        $screen = Screen::findOrFail($id);
        $videoPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
        $screen->video_exists = file_exists($videoPath);
        $screen->video_size_mb = $screen->video_exists
            ? round(filesize($videoPath) / 1024 / 1024, 1)
            : null;
        $screen->immersive_url = $this->immersiveUrl($screen);

        return view('screens.show', [
            'screen' => $screen,
            'adPackages' => self::AD_PACKAGES,
        ]);
    }

    /**
     * Resolve the live 3D cinematic video for a screen: sector pavilions play the
     * sector's depth-parallax cinematic; county booths play the county's immersive
     * showcase. Falls back to null when no 3D asset exists yet.
     */
    private function immersiveUrl($screen): ?string
    {
        $base = '/media/derivatives/holo/';
        // county booth → county immersive showcase
        if (!empty($screen->county_id)) {
            return $base . $screen->county_id . '-immersive.mp4';
        }
        // sector pavilion → sector cinematic (muranga content set)
        if (!empty($screen->sector_id)) {
            $map = [
                'agriculture' => 'muranga-farms',
                'fisheries' => 'muranga-farms',
                'tourism' => 'muranga-tourism',
                'culture' => 'muranga-culture',
                'health' => 'muranga-health',
                'environment' => 'muranga-tourism',
                'education' => 'muranga-institutions',
                'energy' => 'muranga-farms',
                'manufacturing' => 'muranga-products',
                'creative' => 'muranga-culture',
            ];
            $key = $map[$screen->sector_id] ?? 'muranga-tourism';
            return $base . $key . '/cinematic.mp4';
        }
        return null;
    }

    /**
     * Book advertising space on this screen.
     * Creates an Advertisement (pending until payment) + payment intent.
     */
    public function advertise(Request $request, string $id, PaymentService $payments)
    {
        $screen = Screen::findOrFail($id);

        $data = $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:30',
            'package' => 'required|in:day,week,month',
            'target_url' => 'nullable|url|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $pkg = self::AD_PACKAGES[$data['package']];

        $ad = DB::transaction(function () use ($data, $screen, $pkg, $request, $payments) {
            $ad = Advertisement::create([
                'name' => $data['business_name'] . ' — ' . $screen->label,
                'type' => 'screen',
                'placement' => $screen->id,
                'user_id' => $request->user()?->id,
                'target_url' => $data['target_url'] ?? null,
                'budget' => $pkg['price'],
                'starts_at' => now(),
                'ends_at' => now()->addDays($pkg['days']),
                'is_active' => false, // goes live after payment confirmation
            ]);

            $payments->charge($ad, $pkg['price'], [
                'description' => "Screen ad ({$pkg['label']}): {$screen->label}",
                'phone' => $data['phone'],
            ]);

            return $ad;
        });

        \App\Services\N8nService::fire('screen_ad_booked', [
            'screen' => $screen->id, 'advertisement' => $ad->id,
            'business' => $data['business_name'], 'package' => $data['package'],
        ]);

        return back()->with('success',
            "Booking received! Your {$pkg['label']} slot on {$screen->label} is reserved pending payment (KES "
            . number_format($pkg['price']) . "). Our ads team will contact you within 24h to collect your artwork.");
    }

    public function directory()
    {
        $screens = Screen::where('active', true)->orderBy('id')->get()->map(function ($screen) {
            $videoPath = storage_path("app/public/screens/auto_{$screen->id}.mp4");
            $screen->video_exists = file_exists($videoPath);
            $screen->video_size_mb = $screen->video_exists
                ? round(filesize($videoPath) / 1024 / 1024, 1)
                : null;
            $screen->immersive_url = $this->immersiveUrl($screen);

            // Get image count from registry
            $screen->image_count = \App\Models\ScreenImage::where(function ($q) use ($screen) {
                if ($screen->county_id) {
                    $q->where('county_id', $screen->county_id);
                }
                if ($screen->sector_id) {
                    $q->where('sector_ids', 'like', "%{$screen->sector_id}%");
                }
                if (!$screen->county_id && !$screen->sector_id) {
                    $q->whereNotNull('id');
                }
            })->count();

            return $screen;
        });

        return view('screens.directory', compact('screens'));
    }
}

