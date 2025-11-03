<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Profile/Show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Display the user's profile form
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $oldEmail = $user->email;
            
            $user->fill($request->validated());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
                
                $message = "Profile updated successfully! Since you changed your email from {$oldEmail} to {$user->email}, please verify your new email address.";
                
                $user->save();
                
                return Redirect::route('profile.edit')
                    ->with('success', $message);
            }

            $user->save();

            return Redirect::route('profile.edit')
                ->with('success', 'Profile updated successfully! Your changes have been saved.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('profile.edit')
                ->withErrors($e->errors())
                ->with('error', 'Please check the form data and try again.');

        } catch (\Exception $e) {
            return Redirect::route('profile.edit')
                ->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Delete the user's account
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();
            $userName = $user->name;

            Auth::logout();

            $user->delete();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return Redirect::to('/')
                ->with('info', "Account deleted successfully. Goodbye, {$userName}! Your account and all associated data have been permanently removed.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('profile.edit')
                ->withErrors(['password' => 'The provided password is incorrect.'])
                ->with('error', 'Account deletion failed. Please enter your correct password.');

        } catch (\Exception $e) {
            return Redirect::route('profile.edit')
                ->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }

    /**
     * Update the user's password
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $request->user()->update([
                'password' => Hash::make($request->password),
            ]);

            return Redirect::route('profile.edit')
                ->with('success', 'Password updated successfully! Your new password is now active.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return Redirect::route('profile.edit')
                ->withErrors($e->errors())
                ->with('error', 'Password update failed. Please check your current password and try again.');

        } catch (\Exception $e) {
            return Redirect::route('profile.edit')
                ->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }
}
