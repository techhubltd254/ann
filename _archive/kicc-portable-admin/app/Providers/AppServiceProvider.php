<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (file_exists(app_path('Support/helpers.php'))) {
            require_once app_path('Support/helpers.php');
        }
    }

    public function boot(): void
    {
        //
    }
}