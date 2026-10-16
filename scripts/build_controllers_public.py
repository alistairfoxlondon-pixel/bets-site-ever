#!/usr/bin/env python3
"""Write the public facing controllers."""
import os

ROOT = "/home/user/bets-site-ever/app/Http/Controllers"
FILES = {}

FILES['HomeController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $posts = BlogPost::query()
            ->published()
            ->latest('published_at')
            ->limit(3)
            ->get();

        $audited = (int) \App\Models\Audit::query()->where('status', 'completed')->count();
        $sites = (int) \App\Models\Audit::query()->distinct('normalized_url')->count('normalized_url');
        $users = (int) User::query()->count();

        return view('home', [
            'plans' => $plans,
            'posts' => $posts,
            'stats' => [
                'audits' => $audited,
                'sites' => max($sites, 0),
                'users' => $users,
            ],
        ]);
    }
}
'''

FILES['PricingController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use App\Models\Plan;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(Request $request): View
    {
        $currency = $this->currency($request);

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $gateways = Gateway::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Gateway $gateway) => filled($gateway->logo) || filled($gateway->name))
            ->values();

        return view('pricing', [
            'plans' => $plans,
            'gateways' => $gateways,
            'currency' => $currency,
            'rates' => Currency::rates(),
        ]);
    }

    public function features(Request $request): View
    {
        return view('features');
    }

    private function currency(Request $request): string
    {
        $requested = strtoupper((string) $request->query('currency', ''));

        if (in_array($requested, ['USD', 'BDT'], true)) {
            return $requested;
        }

        return 'USD';
    }
}
'''

FILES['BlogController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlogPost::query()->published()->latest('published_at');

        if ($request->filled('tag')) {
            $query->where('tags', 'like', '%'.$request->string('tag').'%');
        }

        $posts = $query->paginate(9)->withQueryString();

        return view('blog.index', [
            'posts' => $posts,
            'tag' => $request->string('tag')->toString(),
        ]);
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()->published()->where('slug', $slug)->firstOrFail();

        $post->increment('views');

        $related = BlogPost::query()
            ->published()
            ->whereKeyNot($post->id)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'related' => $related,
        ]);
    }
}
'''

FILES['ContactController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactController extends Controller
{
    public const CATEGORIES = [
        'general' => 'General question',
        'billing' => 'Billing or refund',
        'technical' => 'Technical problem',
        'sales' => 'Partnership or sales',
        'abuse' => 'Report abuse',
        'other' => 'Something else',
    ];

    public function create(Request $request): View
    {
        return view('contact', [
            'categories' => self::CATEGORIES,
            'preset' => $request->string('reason')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'category' => $data['category'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'status' => 'new',
        ]);

        Log::info('Contact message received', ['category' => $data['category']]);

        return redirect()->route('contact.create')->with('status', 'Message received. We reply to every message within one business day.');
    }
}
'''

FILES['PageController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, Request $request): View
    {
        $page = Page::query()->published()->where('slug', $slug)->first();

        if (! $page) {
            return view('page.generic', ['slug' => $slug]);
        }

        return view('page.show', [
            'page' => $page,
        ]);
    }
}
'''

FILES['ReferralController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function capture(string $code, Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $code = strtoupper($code);

        $affiliate->trackClick(
            $code,
            $request->ip(),
            $request->userAgent(),
            $request->path(),
            $request->header('referer'),
            $request->header('cf-ipcountry'),
        );

        return redirect()->route('register', ['ref' => $code]);
    }
}
'''

FILES['Auth/EmailVerificationController.php'] = r'''<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = $request->user();

        if (! hash_equals((string) $user->getKey(), (string) $id)) {
            abort(403, 'The verification link is not valid for this account.');
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            abort(403, 'The verification link is not valid for this account.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('status', 'Your email address is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('dashboard')->with('status', 'Your email address is verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'A fresh verification link is on its way.');
    }
}
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} public controllers")
