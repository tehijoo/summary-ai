<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $intended = redirect()->intended(RouteServiceProvider::HOME);
        
        // Fix the intended URL if it doesn't include the subfolder path
        if ($request->session()->has('url.intended')) {
            $intendedUrl = $request->session()->get('url.intended');
            if (!str_contains($intendedUrl, '/ringkaskeun')) {
                $intendedUrl = str_replace(config('app.url'), config('app.url'), $intendedUrl);
                return redirect($intendedUrl);
            }
        }

        return $intended;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
