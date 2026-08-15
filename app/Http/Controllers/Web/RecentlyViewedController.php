<?php namespace App\Http\Controllers\Web;
use App\Models\Ecommerce\RecentlyViewed;
use Illuminate\Http\Request;
class RecentlyViewedController extends Controller {
    public function track(Request $r) {
        $data = $r->validate(['viewable_type'=>'required|string','viewable_id'=>'required|integer']);
        $attrs = ['viewable_type'=>$data['viewable_type'],'viewable_id'=>$data['viewable_id']];
        if (auth()->check()) $attrs['user_id'] = auth()->id();
        else $attrs['session_id'] = session()->getId();
        RecentlyViewed::updateOrCreate($attrs, ['viewed_at'=>now()]);
        return response()->json(['ok'=>true]);
    }
    public function get() {
        $query = RecentlyViewed::with('viewable');
        if (auth()->check()) $query->where('user_id', auth()->id());
        else $query->where('session_id', session()->getId());
        return $query->latest('viewed_at')->take(12)->get()->pluck('viewable')->filter();
    }
}