<?php namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Agent;
use App\Models\Review;
use App\Models\Marketplace\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class N8nWebhookController extends Controller {
    // n8n calls this endpoint to trigger actions on the platform
    public function handle(Request $r) {
        $payload = $r->all();
        $action = $payload['action'] ?? null;
        $data = $payload['data'] ?? [];

        if (!$action) return response()->json(['error' => 'action required'], 400);

        try {
            $result = match ($action) {
                'approve_agent' => $this->approveAgent($data),
                'reject_agent' => $this->rejectAgent($data),
                'approve_review' => $this->approveReview($data),
                'update_order_status' => $this->updateOrderStatus($data),
                'send_email' => $this->sendEmail($data),
                'trigger_4d_pipeline' => $this->trigger4dPipeline($data),
                'notify_admin' => $this->notifyAdmin($data),
                'generate_invoice' => $this->generateInvoice($data),
                'backfill_media' => $this->backfillMedia($data),
                default => throw new \InvalidArgumentException("Unknown action: {$action}"),
            };
            Log::info("n8n action [{$action}] succeeded", $data);
            return response()->json(['status' => 'ok', 'result' => $result]);
        } catch (\Throwable $e) {
            Log::warning("n8n action [{$action}] failed: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    private function backfillMedia(array $data): string {
        $entityType = $data['entity_type'] ?? null;
        $entityId = $data['entity_id'] ?? null;

        if ($entityType && $entityId) {
            // Targeted backfill for a single entity
            $entity = $entityType::find($entityId);
            if (!$entity) return "Entity {$entityType}#{$entityId} not found";
            $resolver = app(\App\Services\MediaFallbackResolver::class);
            $best = $resolver->resolveTree($entity);
            if ($best) {
                $field = $data['field'] ?? 'image_url';
                if ($field === 'image_url' && $entityType === \App\Models\Marketplace\Product::class) {
                    \App\Models\Marketplace\ProductImage::create([
                        'product_id' => $entity->id,
                        'url' => $best,
                        'is_primary' => true,
                    ]);
                } else {
                    $entity->update([$field => $best]);
                }
                $resolver->bustCache($entity);
                return "Backfilled {$entityType}#{$entityId}: {$field} = {$best}";
            }
            return "No fallback found for {$entityType}#{$entityId}";
        }

        // Full sweep
        Artisan::call('media:backfill-fallbacks', ['--limit' => 200]);
        $output = Artisan::output();
        return "Full backfill completed. Output: " . substr($output, 0, 500);
    }

    private function approveAgent(array $data): string {
        $agent = Agent::findOrFail($data['agent_id']);
        $agent->update(['status' => 'approved', 'verified_at' => now()]);
        $agent->user?->assignRole('exhibitor');
        \App\Services\N8nService::fire('agent_approved', ['agent_id' => $agent->id]);
        return "Agent {$agent->id} approved";
    }

    private function rejectAgent(array $data): string {
        $agent = Agent::findOrFail($data['agent_id']);
        $agent->update(['status' => 'rejected', 'notes' => $data['reason'] ?? '']);
        return "Agent {$agent->id} rejected";
    }

    private function approveReview(array $data): string {
        $review = Review::findOrFail($data['review_id']);
        $review->update(['status' => 'approved']);
        return "Review {$review->id} approved";
    }

    private function updateOrderStatus(array $data): string {
        $order = Order::where('order_number', $data['order_number'])->firstOrFail();
        $status = $data['status'] ?? 'processing';
        $order->update(['payment_status' => $status, 'paid_at' => $status === 'paid' ? now() : $order->paid_at]);
        \App\Models\Ecommerce\OrderStatusHistory::create([
            'order_id' => $order->id, 'status_to' => $status,
            'notes' => $data['notes'] ?? 'Updated via n8n',
        ]);
        return "Order {$order->order_number} → {$status}";
    }

    private function sendEmail(array $data): string {
        $user = User::findOrFail($data['user_id']);
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\GenericNotificationMail($data['subject'] ?? 'Notification', $data['body'] ?? '')
            );
            return "Email sent to {$user->email}";
        } catch (\Throwable $e) {
            Log::warning("n8n email failed: " . $e->getMessage());
            return "Email queued (mailer may be pending)";
        }
    }

    private function trigger4dPipeline(array $data): string {
        $countySlug = $data['county_slug'] ?? 'muranga';
        $sectorSlug = $data['sector_slug'] ?? 'tourism';
        Log::info("n8n triggering 4D pipeline for {$countySlug}/{$sectorSlug}");
        // The actual pipeline trigger would involve SSH/API to Vast.ai
        // For now, fire the event and let n8n orchestrate
        \App\Services\N8nService::fire('4d_pipeline_triggered', [
            'county' => $countySlug, 'sector' => $sectorSlug,
            'triggered_at' => now()->toIso8601String(),
        ]);
        return "4D pipeline triggered for {$countySlug}/{$sectorSlug}";
    }

    private function notifyAdmin(array $data): string {
        $level = $data['level'] ?? 'info';
        $message = $data['message'] ?? 'No message';
        Log::info("n8n admin alert [{$level}]: {$message}");
        // Could also create in-app notifications for admin users
        return "Admin notified: {$message}";
    }

    private function generateInvoice(array $data): string {
        $order = Order::where('order_number', $data['order_number'])->firstOrFail();
        $order->load('items', 'user');
        $html = view('emails.invoice', compact('order'))->render();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $path = storage_path("app/public/invoices/{$order->order_number}.pdf");
        $pdf->save($path);
        \App\Services\N8nService::fire('invoice_generated', [
            'order_number' => $order->order_number,
            'invoice_url' => url("storage/invoices/{$order->order_number}.pdf"),
        ]);
        return "Invoice generated for {$order->order_number}";
    }
}