<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema; // <--- TAMBAHKAN BARIS INI
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
    }
}