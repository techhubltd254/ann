<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\County;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyInstitution;
use App\Models\CountyTransport;
use App\Models\CountyCultureSite;
use App\Models\CountyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    protected array $syncableModels = [
        'sectors' => Sector::class,
        'sector_entities' => SectorEntity::class,
        'county_tourism_attractions' => CountyTourismAttraction::class,
        'county_hotels' => CountyHotel::class,
        'county_farms' => CountyFarm::class,
        'county_health_facilities' => CountyHealthFacility::class,
        'county_institutions' => CountyInstitution::class,
        'county_transport' => CountyTransport::class,
        'county_culture_sites' => CountyCultureSite::class,
        'county_products' => CountyProduct::class,
    ];

    public function push(Request $request)
    {
        $payload = $request->validate([
            'county_slug' => 'required|string',
            'sync_key_id' => 'required|string',
            'signature' => 'required|string',
            'rows' => 'required|array',
            'rows.*.table' => 'required|string',
            'rows.*.data' => 'required|array',
        ]);

        // 1. Verify sync key
        $key = DB::table('sync_keys')
            ->where('key_id', $payload['sync_key_id'])
            ->where('county_slug', $payload['county_slug'])
            ->where('is_revoked', false)
            ->first();

        if (!$key) {
            return response()->json(['error' => 'Sync key invalid or revoked.'], 403);
        }

        // 2. Verify HMAC signature
        $expectedSig = hash_hmac('sha256', json_encode($payload['rows']), $key->key_hmac);
        if (!hash_equals($expectedSig, $payload['signature'])) {
            $this->logSync('signature_mismatch', null, 'push', 'Signature verification failed');
            return response()->json(['error' => 'Signature mismatch.'], 403);
        }

        // 3. Process each row
        $results = [];
        $county = County::where('slug', $payload['county_slug'])->first();
        if (!$county) {
            return response()->json(['error' => 'County not found.'], 404);
        }

        foreach ($payload['rows'] as $row) {
            $table = $row['table'];
            $data = $row['data'];

            if (!isset($this->syncableModels[$table])) {
                $results[] = ['table' => $table, 'local_id' => $data['local_id'] ?? null, 'result' => 'skipped', 'reason' => 'Unknown table'];
                continue;
            }

            $modelClass = $this->syncableModels[$table];
            $localId = $data['local_id'] ?? null;
            $centralId = $data['central_id'] ?? null;

            try {
                if ($centralId) {
                    // Update existing central row
                    $existing = $modelClass::find($centralId);
                    if (!$existing) {
                        $results[] = ['local_id' => $localId, 'result' => 'conflict_discarded', 'reason' => 'Central row not found'];
                        $this->logSync($table, $localId, 'push', 'Central row not found for central_id=' . $centralId);
                        continue;
                    }

                    // Conflict detection: check content_hash
                    $centralHash = md5(json_encode($existing->toArray()));
                    $baselineHash = $data['_baseline_hash'] ?? null;

                    if ($baselineHash && $centralHash !== $baselineHash) {
                        // Central has changed — central wins
                        $results[] = [
                            'local_id' => $localId,
                            'central_id' => $centralId,
                            'result' => 'conflict_discarded',
                            'reason' => 'Central version changed since snapshot',
                            'central_version' => $existing->toArray(),
                        ];
                        $this->logSync($table, $localId, 'push', 'Conflict: central changed, discarded county update', $data);
                        continue;
                    }

                    // No conflict — apply county's changes
                    unset($data['local_id'], $data['central_id'], $data['_baseline_hash'], $data['sync_status'], $data['synced_at'], $data['created_at'], $data['updated_at']);
                    $existing->update($data);
                    $existing->update(['sync_status' => 'synced', 'synced_at' => now(), 'content_hash' => md5(json_encode($existing->toArray()))]);

                    $results[] = ['local_id' => $localId, 'central_id' => $centralId, 'result' => 'synced'];
                    $this->logSync($table, $localId, 'push', 'synced');
                } else {
                    // New row from offline — insert centrally
                    unset($data['id'], $data['local_id'], $data['central_id'], $data['sync_status'], $data['synced_at'], $data['created_at'], $data['updated_at'], $data['_baseline_hash']);
                    $data['county_id'] = $county->id;
                    $data['is_published'] = $data['is_published'] ?? true;

                    $new = $modelClass::create($data);
                    $new->update(['sync_status' => 'synced', 'synced_at' => now(), 'content_hash' => md5(json_encode($new->toArray()))]);

                    $results[] = ['local_id' => $localId, 'central_id' => $new->id, 'result' => 'created'];
                    $this->logSync($table, $localId, 'push', 'created (new central_id=' . $new->id . ')');
                }
            } catch (\Exception $e) {
                $results[] = ['local_id' => $localId, 'result' => 'error', 'reason' => $e->getMessage()];
                $this->logSync($table, $localId, 'push', 'Error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'results' => $results,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    public function pull(Request $request)
    {
        $payload = $request->validate([
            'county_slug' => 'required|string',
            'sync_key_id' => 'required|string',
            'signature' => 'required|string',
        ]);

        $key = DB::table('sync_keys')
            ->where('key_id', $payload['sync_key_id'])
            ->where('is_revoked', false)
            ->first();

        if (!$key) {
            return response()->json(['error' => 'Sync key invalid or revoked.'], 403);
        }

        $expectedSig = hash_hmac('sha256', $payload['county_slug'], $key->key_hmac);
        if (!hash_equals($expectedSig, $payload['signature'])) {
            return response()->json(['error' => 'Signature mismatch.'], 403);
        }

        $county = County::where('slug', $payload['county_slug'])->first();
        if (!$county) {
            return response()->json(['error' => 'County not found.'], 404);
        }

        $data = [];
        foreach ($this->syncableModels as $table => $modelClass) {
            $rows = $modelClass::where('county_id', $county->id)->get();
            $data[$table] = $rows->toArray();
        }

        return response()->json([
            'county_slug' => $payload['county_slug'],
            'data' => $data,
            'pulled_at' => now()->toIso8601String(),
        ]);
    }

    public function revokeKey(Request $request)
    {
        $payload = $request->validate([
            'key_id' => 'required|string',
        ]);

        DB::table('sync_keys')
            ->where('key_id', $payload['key_id'])
            ->update(['is_revoked' => true, 'revoked_at' => now()]);

        return response()->json(['message' => 'Sync key revoked.']);
    }

    private function logSync(string $table, ?string $localId, string $direction, string $result, ?array $payload = null): void
    {
        try {
            DB::table('sync_log')->insert([
                'table_name' => $table,
                'local_id' => $localId,
                'direction' => $direction,
                'result' => $result,
                'payload' => $payload ? json_encode($payload) : null,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to write sync_log: ' . $e->getMessage());
        }
    }
}
