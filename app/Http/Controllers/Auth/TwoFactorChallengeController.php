<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        $code = preg_replace('/\s+/', '', $request->string('code')->toString()) ?? '';

        if (TwoFactor::verify($user->two_factor_secret, $code)) {
            $request->session()->put('auth.two_factor_confirmed', true);

            return redirect()->intended(route('dashboard'));
        }

        $recoveryCodes = $user->two_factor_recovery_codes ?? [];

        if (in_array(strtoupper($code), array_map('strtoupper', $recoveryCodes), true)) {
            $user->two_factor_recovery_codes = array_values(array_filter(
                $recoveryCodes,
                fn (string $stored) => strtoupper($stored) !== strtoupper($code)
            ));
            $user->save();

            $request->session()->put('auth.two_factor_confirmed', true);

            return redirect()->intended(route('dashboard'))->with('status', 'Recovery code accepted. Store a fresh set of codes in your security settings.');
        }

        throw ValidationException::withMessages(['code' => 'That code was not accepted. Try again.']);
    }

    public function recovery(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        if ($request->isMethod('get')) {
            return view('auth.two-factor-recovery');
        }

        $request->validate(['recovery_code' => ['required', 'string']]);

        $code = strtoupper(preg_replace('/\s+/', '', $request->string('recovery_code')->toString()) ?? '');
        $stored = array_map('strtoupper', $user->two_factor_recovery_codes ?? []);

        if (! in_array($code, $stored, true)) {
            throw ValidationException::withMessages([
                'recovery_code' => 'That recovery code is not valid. Check the sheet you saved when you turned on two factor.',
            ]);
        }

        $user->two_factor_recovery_codes = array_values(array_filter(
            $user->two_factor_recovery_codes ?? [],
            fn (string $saved) => strtoupper($saved) !== $code
        ));
        $user->save();

        $request->session()->put('auth.two_factor_confirmed', true);

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Recovery code accepted. Generate a fresh set of codes in your security settings.');
    }
}
