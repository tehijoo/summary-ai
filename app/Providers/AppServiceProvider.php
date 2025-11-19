<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema; // <--- TAMBAHKAN BARIS INI
use Illuminate\Support\Facades\URL;    // <-- 1. ADD THIS LINE
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191); // <--- TAMBAHKAN BARIS INI
        URL::forceRootUrl('https://senopati.its.ac.id/pbkk-b-7');
        URL::forceScheme('https');
    }
}