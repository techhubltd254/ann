<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TradeAgreement;
use App\Models\TradingBloc;
use Illuminate\Http\Request;

class TradeAgreementController extends Controller
{
    public function index()
    {
        $agreements = TradeAgreement::with('bloc')->active()->latest()->paginate(12);
        $blocs = TradingBloc::where('is_active', true)->withCount('agreements')->get();
        $featured = TradeAgreement::with('bloc')->featured()->active()->latest()->take(3)->get();
        return view('trade-agreements.index', compact('agreements', 'blocs', 'featured'));
    }

    public function show(string $slug)
    {
        $agreement = TradeAgreement::with('bloc', 'categories')->active()->where('slug', $slug)->firstOrFail();
        $related = TradeAgreement::with('bloc')->active()->where('id', '!=', $agreement->id)
            ->where(fn ($q) => $q->where('trading_bloc_id', $agreement->trading_bloc_id)
                ->orWhere('agreement_type', $agreement->agreement_type))
            ->inRandomOrder()->take(4)->get();
        return view('trade-agreements.show', compact('agreement', 'related'));
    }

    public function blocs()
    {
        $blocs = TradingBloc::where('is_active', true)->withCount('agreements', 'counties')->get();
        return view('trade-agreements.blocs', compact('blocs'));
    }

    public function blocShow(string $slug)
    {
        $bloc = TradingBloc::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $agreements = TradeAgreement::with('bloc')->active()->where('trading_bloc_id', $bloc->id)->latest()->paginate(50);
        return view('trade-agreements.bloc-show', compact('bloc', 'agreements'));
    }
}