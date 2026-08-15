<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Wishlist;
use Illuminate\Http\Request;
class WishlistController extends Controller {
    public function index() {
        $items = Wishlist::byUser(auth()->id())->with('wishlistable')->latest()->paginate(24);
        return view('ecommerce.wishlist', compact('items'));
    }
    public function toggle(Request $r) {
        $data = $r->validate(['wishlistable_type' => 'required|string', 'wishlistable_id' => 'required|integer']);
        $existing = Wishlist::where(['user_id'=>auth()->id(),'wishlistable_type'=>$data['wishlistable_type'],'wishlistable_id'=>$data['wishlistable_id']])->first();
        if ($existing) { $existing->delete(); return response()->json(['status'=>'removed']); }
        Wishlist::create($data + ['user_id'=>auth()->id()]);
        return response()->json(['status'=>'added']);
    }
    public function count() {
        return response()->json(['count'=>Wishlist::byUser(auth()->id())->count()]);
    }
}