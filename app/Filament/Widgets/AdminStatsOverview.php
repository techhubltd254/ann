<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\County;
use App\Models\Marketplace\Product;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->icon('heroicon-o-users')
                ->visible(fn () => Auth::user()?->can('view_user')),
            Stat::make('Total Counties', County::count())
                ->icon('heroicon-o-map')
                ->visible(fn () => Auth::user()?->can('view_county')),
            Stat::make('Total Products', Product::count())
                ->icon('heroicon-o-shopping-bag')
                ->visible(fn () => Auth::user()?->can('view_product')),
            Stat::make('Total Bookings', Booking::count())
                ->icon('heroicon-o-receipt-percent')
                ->visible(fn () => Auth::user()?->can('view_booking')),
            Stat::make('Revenue (KES)', number_format(Booking::sum('total'), 2))
                ->icon('heroicon-o-currency-dollar')
                ->visible(fn () => Auth::user()?->can('view_payment')),
        ];
    }
}