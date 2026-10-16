<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
            'referral_code' => ['nullable', 'string', 'max:24'],
        ]);

        $affiliate = app(AffiliateService::class);
        $referralCode = filled($data['referral_code'] ?? null)
            ? Str::upper($data['referral_code'])
            : $affiliate->pendingReferralCode();

        $user = DB::transaction(function () use ($data, $affiliate, $referralCode) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'referral_code' => User::generateReferralCode(),
            ]);

            if (filled($referralCode)) {
                $affiliate->attachReferral($user, $referralCode);
            }

            $signupCredits = (int) (Setting::get('billing.free_signup_credits') ?? config('adready.defaults.free_signup_credits'));

            if ($signupCredits > 0) {
                $user->grantCredits($signupCredits, 'bonus', 'Welcome credits');
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user, remember: (bool) $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('status', 'Welcome to '.config('app.name').'. Your free credits are ready to use.');
    }
}
