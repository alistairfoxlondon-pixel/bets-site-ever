<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        $clientId = (string) (Setting::get('auth.google_client_id') ?: config('services.google.client_id'));

        if (blank($clientId)) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign in is not configured yet.']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['email' => 'Google sign in could not be completed. Please try again.']);
        }

        $email = $googleUser->getEmail();

        if (blank($email)) {
            return redirect()->route('login')->withErrors(['email' => 'Your Google account did not share an email address.']);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = DB::transaction(function () use ($googleUser, $email) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'password' => Str::password(32),
                    'email_verified_at' => now(),
                    'referral_code' => User::generateReferralCode(),
                ]);

                app(AffiliateService::class)->attachReferral($user);

                $signupCredits = (int) (Setting::get('billing.free_signup_credits') ?? config('adready.defaults.free_signup_credits'));

                if ($signupCredits > 0) {
                    $user->grantCredits($signupCredits, 'bonus', 'Welcome credits');
                }

                event(new Registered($user));

                return $user;
            });
        }

        if ($user->isSuspended()) {
            return redirect()->route('login')->withErrors(['email' => 'This account is suspended. Please contact support.']);
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();
        $user->recordLogin(request()->ip());

        return redirect()->intended(route('dashboard'));
    }
}
