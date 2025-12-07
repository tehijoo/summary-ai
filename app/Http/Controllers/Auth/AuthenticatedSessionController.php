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

        // Get the intended URL
        $intended = $request->session()->pull('url.intended');
        
        if ($intended) {
            // Extract just the path from the intended URL
            $parsedUrl = parse_url($intended);
            $path = $parsedUrl['path'] ?? '';
            
            // Remove /ringkaskeun if it already exists (to avoid duplicates)
            $path = str_replace('/ringkaskeun', '', $path);
            
            // Now prepend /ringkaskeun
            $path = '/ringkaskeun' . $path;
            
            $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
            $intended = $path . $query;
            
            return redirect($intended);
        }

        return redirect(RouteServiceProvider::HOME);
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
