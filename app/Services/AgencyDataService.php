<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AgencyDataService — receives, validates, and processes data from
 * external agencies (CBK, IFMIS, KEPHIS, KWS, Ardhisasa, etc.) into
 * the existing pipeline infrastructure.
 *
 * Every pipeline's preFlight() checks agency data availability via
 * canProcess(string $agencyCode). Once an agency integration is live
 * and the regulatory gate clears (earning_locked=false), the pipeline
 * becomes fully processable with real agency-verified data.
 */
class AgencyDataService
{
    public function receive(
        string $agencyCode,
        string $pipelineCode,
        string $eventType,
        array $payload,
        ?string $signature = null
    ): array {
        $source = DB::table('agency_data_sources')->where('code', $agencyCode)->first();
        if (!$source) {
            return ['status' => 'rejected', 'reason' => "Unknown agency: $agencyCode"];
        }

        $id = DB::table('agency_data_events')->insertGetId([
            'agency_code'    => $agencyCode,
            'pipeline_code'  => $pipelineCode,
            'event_type'     => $eventType,
            'payload'        => json_encode($payload),
            'status'         => 'received',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $response = $this->process($agencyCode, $pipelineCode, $eventType, $payload, $id);

        DB::table('agency_data_events')->where('id', $id)->update([
            'status'       => $response['status'] === 'processed' ? 'completed' : 'failed',
            'response'     => json_encode($response),
            'processed_at' => now(),
        ]);

        return $response;
    }

    protected function process(string $agency, string $pipeline, string $eventType, array $payload, int $eventId): array
    {
        try {
            return match ($agency) {
                'KEPHIS' => $this->processKephis($pipeline, $eventType, $payload, $eventId),
                'KWS'    => $this->processKws($pipeline, $eventType, $payload, $eventId),
                'CBK'    => $this->processCbk($pipeline, $eventType, $payload, $eventId),
                'ARDHISASA' => $this->processArdhisasa($pipeline, $eventType, $payload, $eventId),
                'IFMIS'  => $this->processIfmis($pipeline, $eventType, $payload, $eventId),
                'SEZA'   => $this->processSeza($pipeline, $eventType, $payload, $eventId),
                default  => ['status' => 'unhandled', 'agency' => $agency, 'event_type' => $eventType],
            };
        } catch (\Throwable $e) {
            Log::error('agency-data: process failed', [
                'agency' => $agency, 'pipeline' => $pipeline, 'error' => $e->getMessage(),
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /** KEPHIS: export permits → agri pipelines quality scores */
    protected function processKephis(string $pipeline, string $type, array $data, int $eventId): array
    {
        if ($type === 'export_permit') {
            $lotId = $data['lot_id'] ?? null;
            $compliant = $data['compliant'] ?? false;
            $score = $compliant ? 1.0 : 0.3;

            DB::table('quality_scores')->insert([
                'scoreable_type' => 'pipeline:' . $pipeline,
                'scoreable_id'   => $lotId ?? $eventId,
                'score'          => $score,
                'components'     => json_encode(['kephis_compliance' => $score, 'source' => 'KEPHIS']),
                'algorithm_version' => 'kephis-integration-v1',
                'computed_at'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return ['status' => 'processed', 'pipeline' => $pipeline, 'type' => $type, 'score' => $score];
        }
        return ['status' => 'unhandled', 'type' => $type];
    }

    /** KWS: park bookings → tourism pipeline volume */
    protected function processKws(string $pipeline, string $type, array $data, int $eventId): array
    {
        if ($type === 'park_booking') {
            return ['status' => 'processed', 'pipeline' => $pipeline, 'booking_ref' => $data['reference'] ?? null];
        }
        return ['status' => 'unhandled', 'type' => $type];
    }

    /** CBK: regulatory rulings → unlock earning_locked for financing pipelines */
    protected function processCbk(string $pipeline, string $type, array $data, int $eventId): array
    {
        if ($type === 'licence_granted' || $type === 'ruling') {
            DB::table('pipeline_registrations')
                ->where('code', $pipeline)
                ->orWhere('code', 'like', $pipeline . '.%')
                ->update(['earning_locked' => false, 'status' => 'partial', 'updated_at' => now()]);
            Log::info("agency-data: CBK unlock for {$pipeline} and subsectors");
            return ['status' => 'processed', 'pipeline' => $pipeline, 'action' => 'earning_locked=false'];
        }
        return ['status' => 'unhandled', 'type' => $type];
    }

    /** Ardhisasa: title verification → real estate pipeline */
    protected function processArdhisasa(string $pipeline, string $type, array $payload, int $eventId): array
    {
        if ($type === 'title_verified') {
            return ['status' => 'processed', 'pipeline' => $pipeline, 'verified' => $payload['verified'] ?? false];
        }
        return ['status' => 'unhandled', 'type' => $type];
    }

    /** IFMIS: tender/posted PO → procurement pipeline */
    protected function processIfmis(string $pipeline, string $type, array $data, int $eventId): array
    {
        return ['status' => 'processed', 'pipeline' => $pipeline, 'type' => $type];
    }

    /** SEZA: zone registration → investment pipeline */
    protected function processSeza(string $pipeline, string $type, array $data, int $eventId): array
    {
        return ['status' => 'processed', 'pipeline' => $pipeline, 'type' => $type];
    }

    /** Check if a pipeline can process data from an agency */
    public function canProcess(string $pipelineCode, ?string $agencyCode = null): bool
    {
        $pipeline = DB::table('pipeline_registrations')->where('code', $pipelineCode)->first();
        if (!$pipeline) return false;
        if ($pipeline->earning_locked) return false;
        $regs = json_decode($pipeline->regulators ?? '[]', true);
        if ($agencyCode && !in_array($agencyCode, $regs)) return false;
        return true;
    }

    /** Count of pending events per agency */
    public function pendingCounts(): array
    {
        return DB::table('agency_data_events')
            ->selectRaw('agency_code, COUNT(*) as c')
            ->where('status', 'received')
            ->groupBy('agency_code')
            ->orderByDesc('c')
            ->pluck('c', 'agency_code')
            ->all();
    }
}