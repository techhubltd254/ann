<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pipeline\PipelineLicence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Licence upload + approval for gated pipelines.
 * Once a licence is approved, the pipeline's earning_locked flag flips and
 * it becomes fully processable by PipelineEngine — no code changes needed.
 */
class PipelineLicenceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth()->user()?->hasRole('kicc_admin'), 403);
            return $next($request);
        });
    }

    /** Upload a licence document for a pipeline. */
    public function upload(Request $request)
    {
        $data = $request->validate([
            'pipeline_code' => 'required|string|max:20|exists:pipeline_registrations,code',
            'licence_type' => 'required|string|in:cbk,ifmis,ardhisasa,legal,regulatory',
            'reference_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:200',
            'document' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240',
            'notes' => 'nullable|string|max:2000',
            'expires_at' => 'nullable|date',
        ]);

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('licences', 'public');
        }

        PipelineLicence::create([
            'pipeline_code' => $data['pipeline_code'],
            'licence_type' => $data['licence_type'],
            'reference_number' => $data['reference_number'] ?? null,
            'issuing_authority' => $data['issuing_authority'] ?? null,
            'document_path' => $path,
            'notes' => $data['notes'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('kicc.admin', ['tab' => 'licence-queue'])
            ->with('success', "Licence uploaded for {$data['pipeline_code']}. Awaiting approval.");
    }

    /** Approve a pending licence — flips earning_locked to false on the pipeline. */
    public function approve(int $id)
    {
        $licence = PipelineLicence::findOrFail($id);
        abort_if($licence->status !== 'pending', 400, 'Licence is not pending');

        DB::transaction(function () use ($licence) {
            $licence->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            // Flip earning_locked on the pipeline_registrations row
            DB::table('pipeline_registrations')
                ->where('code', $licence->pipeline_code)
                ->update(['earning_locked' => 0]);

            // Also unlock subsector variants (codes starting with the parent prefix)
            DB::table('pipeline_registrations')
                ->where('code', 'like', $licence->pipeline_code . '.%')
                ->update(['earning_locked' => 0]);
        });

        app(\App\Services\CacheSyncService::class)->kicc();

        return redirect()->route('kicc.admin', ['tab' => 'licence-queue'])
            ->with('success', "Licence {$licence->reference_number} approved. Pipeline {$licence->pipeline_code} is now earning.");
    }

    /** Reject a licence. */
    public function reject(int $id)
    {
        $licence = PipelineLicence::findOrFail($id);
        abort_if($licence->status !== 'pending', 400, 'Licence is not pending');

        $licence->update(['status' => 'rejected']);

        return redirect()->route('kicc.admin', ['tab' => 'licence-queue'])
            ->with('error', "Licence for {$licence->pipeline_code} rejected.");
    }

    /** Update pipeline config inline from the Pipeline Settings tab. */
    public function updateConfig(Request $request, string $code)
    {
        $data = $request->validate([
            'status' => 'nullable|string|in:built,partial,licence_gated,blocked',
            'earning_locked' => 'nullable|boolean',
            'regulators' => 'nullable|string',  // JSON
            'economics' => 'nullable|string',   // JSON
            'description' => 'nullable|string|max:2000',
        ]);

        $update = [];

        if (isset($data['status'])) $update['status'] = $data['status'];
        if (isset($data['earning_locked'])) $update['earning_locked'] = (int) $data['earning_locked'];
        if (isset($data['regulators'])) $update['regulators'] = $data['regulators'];
        if (isset($data['economics'])) {
            $current = DB::table('pipeline_registrations')->where('code', $code)->value('economics');
            $eco = json_decode($current ?: '{}', true) ?: [];
            $new = json_decode($data['economics'], true) ?: [];
            $update['economics'] = json_encode(array_merge($eco, $new));
        }

        if (! empty($update)) {
            $update['updated_at'] = now();
            DB::table('pipeline_registrations')->where('code', $code)->update($update);
        }

        app(\App\Services\CacheSyncService::class)->kicc();

        return redirect()->route('kicc.admin', ['tab' => 'pipeline-settings'])
            ->with('success', "Pipeline {$code} config updated.");
    }

    /** List all pipelines with their earning_locked status. */
    public function pipelineSettingsData(): array
    {
        $pipelines = DB::table('pipeline_registrations')
            ->orderBy('sector')->orderBy('code')
            ->get(['code', 'sector', 'status', 'earning_locked', 'regulators', 'economics', 'slug', 'phase'])
            ->toArray();

        // Count licences per pipeline
        $licenceCounts = DB::table('pipeline_licences')
            ->selectRaw('pipeline_code, COUNT(*) as total, SUM(IF(status="approved",1,0)) as approved')
            ->groupBy('pipeline_code')
            ->get()
            ->keyBy('pipeline_code');

        return [
            'pipelines' => $pipelines,
            'licenceCounts' => $licenceCounts,
        ];
    }
}