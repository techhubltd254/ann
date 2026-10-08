<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exhibition;
use App\Models\Booking;
use App\Models\Marketplace\Order;
use App\Models\Marketplace\Product;
use App\Models\EscrowTransaction;
use App\Models\Ecommerce\GiftCard;
use App\Models\Review;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $stats = [
            'total_bookings' => Booking::where('user_id', $user->id)->count(),
            'upcoming_bookings' => Booking::where('user_id', $user->id)
                ->whereHas('exhibition', fn($q) => $q->where('start_date', '>=', now()))
                ->count(),
            'exhibitions' => Exhibition::where('organizer_info->email', $user->email)->count(),
            'orders' => Order::where('user_id', $user->id)->count(),
            'reviews' => Review::where('user_id', $user->id)->count(),
            'referrals' => $user->metadata['referral_count'] ?? 0,
        ];

        $recentOrders = Order::where('user_id', $user->id)->latest()->take(5)->get();

        return view('experience.pages.dashboard.index', compact('stats', 'recentOrders'));
    }

    public function exhibitions()
    {
        $user = auth()->user();
        $exhibitions = Exhibition::where('organizer_info->email', $user->email)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('experience.pages.dashboard.exhibitions', compact('exhibitions'));
    }

    public function bookings()
    {
        $bookings = Booking::with(['exhibition', 'booths'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        return view('experience.pages.dashboard.bookings', compact('bookings'));
    }

    public function profile()
    {
        return view('experience.pages.dashboard.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $user->update($request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]));
        return redirect()->route('dashboard.profile')->with('success', 'Profile updated.');
    }

    // ── ADDRESS BOOK ──

    public function addresses()
    {
        $user = auth()->user();
        $addresses = $user->metadata['addresses'] ?? [];
        return view('experience.pages.dashboard.addresses', compact('addresses'));
    }

    public function saveAddress(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'label' => 'required|string|max:50',
            'county' => 'required|string|max:100',
            'town' => 'required|string|max:100',
            'address' => 'required|string|max:200',
            'phone' => 'nullable|string|max:20',
            'default' => 'nullable|boolean',
        ]);

        $meta = $user->metadata ?? [];
        $addresses = $meta['addresses'] ?? [];
        $id = $request->input('id', count($addresses) + 1);

        $addresses[] = [
            'id' => $id,
            'label' => $data['label'],
            'county' => $data['county'],
            'town' => $data['town'],
            'address' => $data['address'],
            'phone' => $data['phone'] ?? $user->phone,
            'default' => $request->boolean('default'),
        ];

        $meta['addresses'] = $addresses;
        $user->forceFill(['metadata' => $meta])->save();

        return redirect()->route('dashboard.addresses')->with('success', 'Address saved.');
    }

    public function deleteAddress(int $id)
    {
        $user = auth()->user();
        $meta = $user->metadata ?? [];
        $addresses = $meta['addresses'] ?? [];
        $meta['addresses'] = array_values(array_filter($addresses, fn($a) => ($a['id'] ?? 0) !== $id));
        $user->forceFill(['metadata' => $meta])->save();
        return redirect()->route('dashboard.addresses')->with('success', 'Address deleted.');
    }

    // ── SECURITY ──

    public function security()
    {
        $user = auth()->user();
        return view('experience.pages.dashboard.security', compact('user'));
    }

    public function toggle2fa(Request $request)
    {
        $user = auth()->user();
        $user->forceFill(['mfa_enabled' => !($user->mfa_enabled ?? false)])->save();

        $status = $user->mfa_enabled ? 'enabled' : 'disabled';
        return redirect()->route('dashboard.security')->with('success', "2FA {$status}.");
    }

    // ── MY REVIEWS ──

    public function myReviews()
    {
        $reviews = Review::where('user_id', auth()->id())
            ->latest()->paginate(10);
        return view('experience.pages.dashboard.reviews', compact('reviews'));
    }

    // ── REFERRALS ──

    public function referrals()
    {
        $user = auth()->user();
        $referralCode = $user->metadata['referral_code'] ?? substr(md5($user->email), 0, 8);
        $referralUrl = route('register', ['ref' => $referralCode]);
        $referralCount = $user->metadata['referral_count'] ?? 0;
        $referralEarnings = $user->metadata['referral_earnings'] ?? 0;

        return view('experience.pages.dashboard.referrals', compact('referralCode', 'referralUrl', 'referralCount', 'referralEarnings'));
    }

    // ── GIFT CARDS ──

    public function giftCards()
    {
        $cards = GiftCard::where('user_id', auth()->id())
            ->where('balance', '>', 0)
            ->latest()
            ->get();
        return view('experience.pages.dashboard.gift-cards', compact('cards'));
    }

    // ── SELLER DASHBOARD ──

    public function seller()
    {
        $user = auth()->user();
        $products = Product::where('user_id', $user->id)->get();
        $orders = Order::whereHas('items.variant.product', fn($q) => $q->where('user_id', $user->id))->count();
        $earnings = EscrowTransaction::where('seller_id', $user->id)
            ->where('status', 'released')->sum('amount');

        // Top-selling products
        $topProducts = Product::where('user_id', $user->id)
            ->withCount(['orderItems as total_sold' => fn($q) => $q->selectRaw('COALESCE(SUM(quantity), 0)')])
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return view('experience.pages.dashboard.seller', compact('products', 'orders', 'earnings', 'topProducts'));
    }
}