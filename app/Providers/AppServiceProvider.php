<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema; // <--- TAMBAHKAN BARIS INI
use Illuminate\Support\Facades\URL;
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
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
            if ($scheme = parse_url($appUrl, PHP_URL_SCHEME)) {
                URL::forceScheme($scheme);
            }
        }
    }
}