<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic page — shows which database the app is actually connected to
 * and basic row counts. Used to verify the deployed app reads from TiDB
 * (not a platform-injected empty MySQL).
 */
class DbCheckController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $connection = config('database.default');
        $conn = config("database.connections.{$connection}", []);
        $host = $conn['host'] ?? '(unknown)';
        $port = $conn['port'] ?? '(unknown)';
        $database = $conn['database'] ?? '(unknown)';
        $user = $conn['username'] ?? '(unknown)';

        $status = 'ok';
        $error = null;
        $counts = [];

        try {
            DB::select('SELECT 1');
            foreach (['counties', 'sectors', 'users', 'products', 'media_assets', 'exhibitions', 'venues'] as $table) {
                try {
                    if (Schema::hasTable($table)) {
                        $counts[$table] = (int) DB::table($table)->count();
                    } else {
                        $counts[$table] = null;
                    }
                } catch (\Throwable $e) {
                    $counts[$table] = null;
                }
            }
        } catch (\Throwable $e) {
            $status = 'error';
            $error = $e->getMessage();
        }

        return response()->json([
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'default_connection' => $connection,
            'database' => [
                'host' => $host,
                'port' => $port,
                'name' => $database,
                'user' => $user,
            ],
            'is_tidb' => str_contains((string) $host, 'tidbcloud.com'),
            'status' => $status,
            'error' => $error,
            'table_counts' => $counts,
            'time' => now()->toIso8601String(),
        ]);
    }
}