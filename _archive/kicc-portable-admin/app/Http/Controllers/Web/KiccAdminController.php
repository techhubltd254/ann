<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\County;
use App\Models\EscrowTransaction;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\Ministry;
use App\Models\Payment\PaymentIntent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * KICC Overall Admin Portal — the platform owner's god-mode.
 *
 * Controls all four exhibitor tiers: national government, counties,
 * private exhibitors, and the marketplace itself. Every entity links
 * out to its independent public website from here.
 */
class KiccAdminController extends Controller
{
    protected function authorizeKicc(): void
    {
        if (!Auth::user()?->hasRole('kicc_admin')) {
            abort(403, 'KICC admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeKicc();
        return Inertia::render('app/dashboards');
    }

    /** KICC releases escrow funds to a seller after delivery confirmation. */
    public function releaseEscrow(int $id)
    {
        $this->authorizeKicc();
        $escrow = EscrowTransaction::findOrFail($id);
        $steps = collect($escrow->steps ?? [])->map(fn ($s) => array_merge($s, ['done' => true]))->values()->all();
        $escrow->update(['status' => 'released', 'steps' => $steps, 'current_step' => 4, 'released_at' => now()]);
        return redirect()->route('kicc.admin', ['tab' => 'escrow'])->with('success', "Escrow {$escrow->escrow_id} released to {$escrow->seller?->name}.");
    }

    /** KICC certifies a provider's service/price change (govt certification). */
    public function approveService(string $table, int $id)
    {
        $this->authorizeKicc();
        $allowed = ['flight_inventory', 'hotel_rooms', 'airport_transfers', 'flights'];
        abort_unless(in_array($table, $allowed), 404);

        $update = ['is_active' => 1, 'updated_at' => now()];
        if ($table === 'flights') $update = ['status' => 'active', 'updated_at' => now()];
        \Illuminate\Support\Facades\DB::table($table)->where('id', $id)->update($update);

        \App\Services\N8nService::fire('provider_service_approved', ['table' => $table, 'id' => $id]);
        return redirect()->route('kicc.admin', ['tab' => 'providers'])->with('success', 'Service certified and now live.');
    }

    /** Trigger data sync between SQLite and TiDB Cloud. */
    public function sync(Request $request)
    {
        $this->authorizeKicc();
        $direction = $request->input('direction', 'from');
        $tables = $request->input('tables', 'all');

        if ($direction === 'to') {
            $exitCode = Artisan::call('sync:to-tidb', ['--tables' => $tables]);
            $msg = $exitCode === 0 ? 'Data pushed to TiDB Cloud ✅' : 'Sync failed ❌';
        } else {
            $exitCode = Artisan::call('sync:from-tidb', ['--tables' => $tables]);
            $msg = $exitCode === 0 ? 'Data pulled from TiDB Cloud ✅' : 'Sync failed ❌';
        }

        $output = Artisan::output();
        return redirect()->route('kicc.admin', ['tab' => 'overview'])->with('success', $msg)->with('sync_output', $output);
    }

    /** Trigger county-level data sync. */
    public function syncCounty(Request $request)
    {
        $this->authorizeKicc();
        $countyTables = ['county_products', 'county_tourism_attractions', 'county_hotels',
            'county_farms', 'county_health_facilities', 'county_institutions',
            'county_transport', 'county_culture_sites'];

        $direction = $request->input('direction', 'from');
        $tables = implode(',', $countyTables);
        $command = $direction === 'to' ? 'sync:to-tidb' : 'sync:from-tidb';

        $exitCode = Artisan::call($command, ['--tables' => $tables]);
        $msg = $exitCode === 0 ? "County data {$direction} TidB ✅" : 'Sync failed ❌';

        return redirect()->back()->with('success', $msg);
    }
}
