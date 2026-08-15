<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\ProductQuestion;
use Illuminate\Http\Request;
class ProductQAController extends Controller {
    public function index($productId) {
        return ProductQuestion::where('product_id', $productId)->whereNotNull('answer')->with('user')->latest()->get();
    }
    public function ask(Request $r, $productId) {
        $data = $r->validate(['question'=>'required|string|max:2000']);
        $q = ProductQuestion::create(['product_id'=>$productId, 'user_id'=>auth()->id(), 'question'=>$data['question']]);
        try { (new \App\Services\N8nService())->fire('product_question_asked', $q->toArray()); } catch (\Throwable $e) {}
        return back()->with('success', 'Question submitted.');
    }
    public function answer(Request $r, $id) {
        $q = ProductQuestion::findOrFail($id);
        abort_if($q->product->user_id !== auth()->id() && !auth()->user()?->isAdmin(), 403);
        $data = $r->validate(['answer'=>'required|string|max:2000']);
        $q->update(['answer'=>$data['answer'], 'answered_at'=>now()]);
        return back()->with('success', 'Answered.');
    }
}