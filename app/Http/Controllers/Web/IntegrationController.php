<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\County;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    // ─── GIS MAPS ───
    public function map(County $county)
    {
        return view('integrations.map', compact('county'));
    }

    // ─── WEATHER ───
    public function weather(County $county)
    {
        $weather = null;
        $key = env('OPENWEATHER_API_KEY');
        $lat = $county->latitude;
        $lon = $county->longitude;

        if ($key && $lat && $lon) {
            try {
                $res = Http::timeout(10)->get('https://api.openweathermap.org/data/2.5/weather', [
                    'lat' => $lat, 'lon' => $lon, 'appid' => $key, 'units' => 'metric',
                ]);
                $weather = $res->json();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Weather for {$county->slug}: " . $e->getMessage());
            }
        }
        return view('integrations.weather', compact('county', 'weather'));
    }

    // ─── INTEGRATION SETTINGS (admin) ───
    public function settings()
    {
        $integrations = [
            ['name' => 'GIS Maps (Leaflet)', 'status' => 'active', 'config' => 'Mapbox/OpenStreetMap tiles'],
            ['name' => 'OpenWeather API', 'status' => env('OPENWEATHER_API_KEY') ? 'active' : 'inactive', 'config' => 'Configured'],
            ['name' => 'Hotel PMS (Mews/Cloudbeds)', 'status' => 'pending', 'config' => 'Awaiting provider credentials'],
            ['name' => 'GDS / Airline (Amadeus)', 'status' => 'pending', 'config' => 'Awaiting API credentials'],
            ['name' => 'National ID (Huduma Namba)', 'status' => 'pending', 'config' => 'Awaiting government API'],
            ['name' => 'CRM (Salesforce)', 'status' => 'pending', 'config' => 'Webhook URL configured'],
            ['name' => 'ERP (Odoo/ERPNext)', 'status' => 'pending', 'config' => 'Awaiting API credentials'],
            ['name' => 'SendGrid Email', 'status' => env('SENDGRID_API_KEY') ? 'active' : 'inactive', 'config' => 'Transactional emails'],
            ['name' => 'M-Pesa Payments', 'status' => 'active', 'config' => 'STK Push via Daraja API'],
            ['name' => 'Stripe Payments', 'status' => env('STRIPE_KEY') ? 'active' : 'inactive', 'config' => env('STRIPE_KEY') ? 'Live mode' : 'Not configured'],
        ];
        return view('integrations.settings', compact('integrations'));
    }

    public function updateWeatherKey(Request $request)
    {
        $data = $request->validate(['openweather_api_key' => 'required|string|max:255']);
        file_put_contents(base_path('.env'), str_replace(
            'OPENWEATHER_API_KEY=' . env('OPENWEATHER_API_KEY'),
            'OPENWEATHER_API_KEY=' . $data['openweather_api_key'],
            file_get_contents(base_path('.env'))
        ));
        return back()->with('success', 'Weather API key updated. Run config:cache to apply.');
    }
}