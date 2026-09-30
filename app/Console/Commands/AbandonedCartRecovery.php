<?php namespace App\Console\Commands;
use App\Models\Marketplace\ShoppingCart;
use App\Services\N8nService;
use App\Events\GenericDomainEvent;
use Illuminate\Console\Command;
class AbandonedCartRecovery extends Command {
    protected $signature = 'carts:recover';
    protected $description = 'Send abandoned cart recovery emails';
    public function handle() {
        $cutoff = now()->subHours(2);
        $carts = ShoppingCart::with('items.variant.product')
            ->whereNull('user_id')->where('created_at', '<', $cutoff)
            ->where('created_at', '>', now()->subDay())
            ->whereDoesntHave('order')
            ->get();
        $count = 0;
        foreach ($carts as $cart) {
            try { event(new GenericDomainEvent('abandoned_cart', ['cart_id'=>$cart->id,'items'=>$cart->items->count()], n8nEventName: 'abandoned_cart')); $count++; } catch (\Throwable $e) {}
        }
        $this->info("Fired abandoned cart recovery for {$count} carts.");
    }
}