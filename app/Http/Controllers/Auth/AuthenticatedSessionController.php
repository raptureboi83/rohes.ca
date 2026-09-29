<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
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

        return redirect()->intended($this->homeFor($request->user()));
    }

    /**
     * Send an administrator to the CMS and everyone else to the public home page.
     *
     * Breeze ships with a route named "dashboard" at /dashboard, but this project
     * keeps the CMS under the admin name prefix, so the route is "admin.dashboard".
     */
    protected function homeFor(?User $user): string
    {
        return $user?->is_admin
            ? route('admin.dashboard', absolute: false)
            : route('home', absolute: false);
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
