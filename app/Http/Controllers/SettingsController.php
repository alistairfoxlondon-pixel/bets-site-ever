<?php

namespace App\Http\Controllers;

use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function profile(Request $request): View
    {
        return view('settings.profile', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'in:USD,BDT'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'email_notifications' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $user->fill($data);
        $user->email_notifications = $request->boolean('email_notifications');
        $user->save();

        return back()->with('status', 'Profile updated.');
    }

    public function security(Request $request): View
    {
        return view('settings.security', [
            'user' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withInput()->with('error', 'The current password is not correct.');
        }

        $user->password = $data['password'];
        $user->save();

        return back()->with('status', 'Password updated.');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->withInput()->with('error', 'The password is not correct.');
        }

        $user->email = $data['email'];
        $user->email_verified_at = null;
        $user->save();

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'Email address updated. Check your inbox to confirm the new address.');
    }

    public function twoFactor(Request $request): View
    {
        return view('settings.two-factor', [
            'user' => $request->user(),
        ]);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', 'Two factor authentication is already on.');
        }

        $secret = TwoFactor::generateSecret();

        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = false;
        $user->save();

        return back()->with('status', 'Scan the code, then confirm with a code from your app.');
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! filled($user->two_factor_secret)) {
            return back()->with('error', 'Start the setup again.');
        }

        if (! TwoFactor::verify($user->two_factor_secret, $data['code'])) {
            return back()->with('error', 'That code was not accepted. Try again.');
        }

        $recoveryCodes = TwoFactor::recoveryCodes();

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return redirect()->route('settings.two-factor')
            ->with('status', 'Two factor authentication is on. Save your recovery codes somewhere safe.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->with('error', 'The password is not correct.');
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return back()->with('status', 'Two factor authentication is off.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        $codes = TwoFactor::recoveryCodes();

        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return back()
            ->with('status', 'A fresh set of recovery codes was generated.')
            ->with('recovery_codes', $codes);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            return back()->with('error', 'The password is not correct.');
        }

        $user->delete();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Your account was closed.');
    }
}
