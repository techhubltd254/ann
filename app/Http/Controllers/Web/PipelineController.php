<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PipelineRouter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Public pipeline sector pages — shows all pipelines in a sector.
 */
class PipelineController extends Controller
{
    public function index()
    {
        $sectors = DB::table('pipeline_registrations')
            ->selectRaw('sector, COUNT(*) as total, SUM(IF(earning_locked=0,1,0)) as active, SUM(IF(earning_locked=1,1,0)) as locked')
            ->groupBy('sector')
            ->orderByDesc('total')
            ->get();

        $totals = [
            'total' => $sectors->sum('total'),
            'active' => $sectors->sum('active'),
            'locked' => $sectors->sum('locked'),
        ];

        return view('experience.pages.pipelines.index', compact('sectors', 'totals'));
    }

    public function sector(string $sector, PipelineRouter $resolver)
    {
        $pipelines = DB::table('pipeline_registrations')
            ->where('sector', $sector)
            ->orderBy('code')
            ->get(['code', 'slug', 'phase', 'status', 'earning_locked', 'regulators', 'economics', 'parent']);

        if ($pipelines->isEmpty()) abort(404);

        $sectorName = Str::title(str_replace('-', ' ', $sector));
        $parentPipeline = $pipelines->first(fn ($p) => is_null($p->parent) || $p->parent === '');
        $defaultFeeRate = $parentPipeline ? $resolver->feeRate($parentPipeline->code) : 4.0;

        $totalGmv = DB::table('escrow_transactions')
            ->whereIn('reference_type', $pipelines->pluck('code'))
            ->where('status', 'released')
            ->sum('amount');

        return view('experience.pages.pipelines.sector', compact(
            'pipelines', 'sector', 'sectorName',
            'defaultFeeRate', 'totalGmv'
        ));
    }

    public function show(string $code, PipelineRouter $resolver)
    {
        $pipeline = DB::table('pipeline_registrations')->where('code', $code)->first();
        if (! $pipeline) abort(404);

        $economics = json_decode($pipeline->economics ?? '{}', true) ?: [];
        $regulators = json_decode($pipeline->regulators ?? '[]', true) ?: [];
        $feeRate = $resolver->feeRate($code);

        $earnings = DB::table('escrow_transactions')
            ->where('reference_type', $code)
            ->where('status', 'released')
            ->selectRaw('COUNT(*) as trades, SUM(amount) as gmv, SUM(IF(synthetic=1,1,0)) as synthetic_trades')
            ->first();

        return view('experience.pages.pipelines.show', compact(
            'pipeline', 'economics', 'regulators', 'feeRate', 'earnings'
        ));
    }
}