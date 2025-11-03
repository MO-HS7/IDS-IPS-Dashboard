<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            $user = Auth::user();
            $userName = $user->name;
            $userRole = $user->role ?? 'User';

            // رسالة ترحيب مخصصة
            $welcomeMessage = "Welcome back, {$userName}! You have successfully logged in as {$userRole}.";
            
            // إضافة معلومات إضافية للمسؤولين
            if ($userRole === 'Admin') {
                $welcomeMessage .= " Admin dashboard is now available.";
            }

            return redirect()->intended(RouteServiceProvider::HOME)
                ->with('success', $welcomeMessage);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', 'Invalid credentials. Please check your email and password.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Login failed. Please try again later.');
        }
    }

    /**
     * Destroy an authenticated session
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $userName = Auth::user()->name ?? 'User';

            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('info', "Goodbye, {$userName}! You have been logged out successfully. See you next time!");

        } catch (\Exception $e) {
            return redirect('/')
                ->with('error', 'Logout failed. Please clear your browser cookies.');
        }
    }
}
