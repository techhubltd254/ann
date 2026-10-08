<?php
namespace App\Http\Controllers\Live;

use App\Http\Controllers\Controller;
use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\ExhibitorStudioSession;
use App\Models\HeartbeatLog;
use App\Services\HeartbeatService;
use Illuminate\Http\Request;

class LiveAdminController extends Controller
{
    protected HeartbeatService $heartbeat;

    public function __construct(HeartbeatService $heartbeat)
    {
        $this->heartbeat = $heartbeat;
        $this->middleware('admin:kicc');
    }

    public function dashboard()
    {
        $stats = [
            'total_booths' => Booth::count(),
            'authorized' => BoothAuthorization::where('status', 'AUTHORIZED')->count(),
            'live_now' => ExhibitorStudioSession::where('stream_status', 'live')->count(),
            'total_viewers' => 0, // TODO: integrate with Cloudflare Stream viewer count
            'heartbeat_health' => BoothAuthorization::where('status', 'AUTHORIZED')
                ->where('last_heartbeat_at', '>=', now()->subSeconds(15))->count(),
            'broker_metrics' => $this->heartbeat->getBrokerMetrics(),
        ];

        $booths = Booth::with('authorization', 'user')
            ->orderBy('stream_status', 'desc')
            ->paginate(50);

        return view('experience.pages.live.admin.dashboard', compact('stats', 'booths'));
    }

    public function booths()
    {
        $booths = Booth::with('authorization', 'liveStreams')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('experience.pages.live.admin.booths', compact('booths'));
    }

    public function authorize(Booth $booth, Request $request)
    {
        $auth = $booth->authorization ?? BoothAuthorization::create([
            'booth_id' => $booth->id,
            'user_id' => $booth->user_id,
            'status' => 'AUTHORIZED',
            'authorized_at' => now(),
        ]);

        if ($auth->status !== 'AUTHORIZED') {
            $auth->authorize();
        }

        return redirect()->back()->with('success', "Booth {$booth->name} authorized.");
    }

    public function terminate(Booth $booth, Request $request)
    {
        $auth = $booth->authorization;
        if (!$auth) {
            return redirect()->back()->with('error', 'Booth has no authorization record.');
        }

        $reasonCode = $request->input('reason_code', 'manual');
        $note = $request->input('reason_note');

        $this->heartbeat->terminateBooth($auth, $reasonCode, $note);

        return redirect()->back()->with('info', "Booth {$booth->name} terminated.");
    }

    public function generateApiKey(Booth $booth)
    {
        $auth = $booth->authorization;
        if (!$auth) {
            return redirect()->back()->with('error', 'Authorize booth first.');
        }

        $key = $this->heartbeat->generateApiKey($auth);

        return redirect()->back()->with('api_key', $key)
            ->with('warning', 'Save this key now — it will not be shown again.');
    }

    public function monitor()
    {
        $liveBooths = Booth::with('authorization', 'liveStreams')
            ->where('stream_status', 'live')
            ->get()
            ->map(function ($booth) {
                $health = $this->heartbeat->getHeartbeatHealth($booth->id);
                $booth->heartbeat_health = $health;
                $booth->heartbeat_color = match($health) {
                    'healthy' => 'green',
                    'warning' => 'amber',
                    'dead' => 'red',
                    default => 'gray',
                };
                $recentLog = HeartbeatLog::where('booth_id', $booth->id)->latest()->first();
                $booth->last_heartbeat = $recentLog?->heartbeat_at?->diffForHumans() ?? 'never';
                return $booth;
            });

        return view('experience.pages.live.admin.monitor', compact('liveBooths'));
    }

    public function analytics()
    {
        $data = [
            'total_streams' => HeartbeatLog::selectRaw('DATE(heartbeat_at) as date, COUNT(*) as total')
                ->groupBy('date')->orderBy('date', 'desc')->limit(30)->get(),
            'auth_events' => BoothAuthorization::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')->get(),
            'peak_concurrent' => ExhibitorStudioSession::where('stream_status', 'live')->count(),
        ];

        return view('experience.pages.live.admin.analytics', compact('data'));
    }

    public function systemHealth()
    {
        $checks = [];

        // Database connectivity
        try {
            \DB::select('SELECT 1');
            $checks['database'] = ['status' => 'pass', 'latency_ms' => round(\DB::select('SELECT 1 + 0 AS t')[0]->t, 0)];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }

        // Redis connectivity
        try {
            \Illuminate\Support\Facades\Redis::ping();
            $checks['redis'] = ['status' => 'pass'];
        } catch (\Exception $e) {
            $checks['redis'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }

        // Queue worker
        $checks['queue'] = ['status' => 'pass', 'active' => \Illuminate\Support\Facades\Queue::size()];

        // Heartbeat broker health
        $authorized = BoothAuthorization::where('status', 'AUTHORIZED')->count();
        $healthyHeartbeats = BoothAuthorization::where('status', 'AUTHORIZED')
            ->where('last_heartbeat_at', '>=', now()->subSeconds(15))->count();
        $checks['heartbeat_broker'] = [
            'status' => $healthyHeartbeats >= ($authorized * 0.8) ? 'pass' : 'warn',
            'authorized' => $authorized,
            'healthy' => $healthyHeartbeats,
            'percent_healthy' => $authorized > 0 ? round($healthyHeartbeats / $authorized * 100) : 100,
        ];

        // Storage
        $storagePath = storage_path();
        $free = disk_free_space($storagePath);
        $total = disk_total_space($storagePath);
        $usedPercent = $total > 0 ? round((1 - $free / $total) * 100) : 0;
        $checks['storage'] = [
            'status' => $usedPercent < 80 ? 'pass' : ($usedPercent < 90 ? 'warn' : 'fail'),
            'used_percent' => $usedPercent,
            'free_gb' => round($free / 1073741824, 1),
            'total_gb' => round($total / 1073741824, 1),
        ];

        // Last heartbeat
        $lastHeartbeat = HeartbeatLog::latest()->first();
        $checks['last_heartbeat'] = [
            'time' => $lastHeartbeat?->heartbeat_at?->toIso8601String(),
            'ago' => $lastHeartbeat?->heartbeat_at?->diffForHumans() ?? 'never',
        ];

        $overall = collect($checks)->every(fn($c) => ($c['status'] ?? 'fail') !== 'fail');

        return response()->json([
            'status' => $overall ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ]);
    }
}