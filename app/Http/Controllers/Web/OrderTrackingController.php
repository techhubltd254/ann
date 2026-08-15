<?php namespace App\Http\Controllers\Web;
use App\Models\Marketplace\Order;
use App\Models\Ecommerce\OrderStatusHistory;
use App\Models\Ecommerce\ReturnRequest;
use Illuminate\Http\Request;
class OrderTrackingController extends Controller {
    public function show($orderNumber) {
        $order = Order::where('order_number', $orderNumber)->with(['items.product','items.variant','statusHistory.user'])->firstOrFail();
        abort_if($order->user_id !== auth()->id() && !auth()->user()?->isAdmin(), 403);
        $returns = ReturnRequest::where('order_id', $order->id)->get();
        return view('ecommerce.order-tracking', compact('order','returns'));
    }
    public function myOrders() {
        $orders = Order::where('user_id', auth()->id())->with('items')->latest()->paginate(15);
        return view('ecommerce.my-orders', compact('orders'));
    }
    public function requestReturn(Request $r, $orderNumber) {
        $order = Order::where('order_number', $orderNumber)->where('user_id', auth()->id())->firstOrFail();
        $data = $r->validate(['order_item_id'=>'required|exists:order_items,id','reason'=>'required|string|max:1000']);
        $item = $order->items()->findOrFail($data['order_item_id']);
        $existing = ReturnRequest::where(['order_item_id'=>$item->id,'user_id'=>auth()->id()])->whereIn('status',['pending','approved'])->first();
        if ($existing) return back()->with('error', 'Return already requested for this item.');
        $return = ReturnRequest::create([
            'return_number' => 'RET-'.strtoupper(substr(uniqid(), -8)),
            'order_id' => $order->id, 'user_id' => auth()->id(),
            'order_item_id' => $item->id, 'reason' => $data['reason'],
        ]);
        try { (new \App\Services\N8nService())->fire('return_requested', $return->toArray()); } catch (\Throwable $e) {}
        return back()->with('success', 'Return request submitted.');
    }
}