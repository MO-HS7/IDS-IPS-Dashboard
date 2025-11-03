<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return redirect()->intended(RouteServiceProvider::HOME.'?verified=1')
                    ->with('info', 'Your email was already verified. Welcome back to AI-IDS!');
            }

            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }

            $userName = $request->user()->name;

            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1')
                ->with('success', "Congratulations, {$userName}! Your email has been successfully verified. You now have full access to all AI-IDS features.");

        } catch (\Exception $e) {
            return redirect()->intended(RouteServiceProvider::HOME)
                ->with('error', 'Email verification failed. Please try clicking the verification link again or request a new one.');
        }
    }
}
