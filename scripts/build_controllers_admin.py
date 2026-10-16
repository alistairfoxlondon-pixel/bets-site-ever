#!/usr/bin/env python3
"""Write the administration controllers."""
import os

ROOT = "/home/user/bets-site-ever/app/Http/Controllers/Admin"
FILES = {}

FILES['DashboardController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\ContentDocument;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Billing\GatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, GatewayManager $gateways): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $revenue = (float) Invoice::query()->where('status', 'paid')->sum('amount');
        $revenueThisMonth = (float) Invoice::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', $startOfMonth)
            ->sum('amount');

        $users = User::query()->count();
        $newUsers = User::query()->where('created_at', '>=', $startOfMonth)->count();
        $activeSubscriptions = Subscription::query()->whereIn('status', ['active', 'trialing'])->count();
        $audits = Audit::query()->where('status', 'completed')->count();
        $documents = ContentDocument::query()->count();
        $words = (int) ContentDocument::query()->sum('word_count');

        $daily = Invoice::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->get(['paid_at', 'amount'])
            ->groupBy(fn ($invoice) => $invoice->paid_at->format('Y-m-d'))
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->all();

        $chart = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i)->format('Y-m-d');
            $chart[] = ['date' => $day, 'amount' => $daily[$day] ?? 0.0];
        }

        $topAudits = Audit::query()
            ->where('status', 'completed')
            ->orderByDesc('probability')
            ->limit(5)
            ->get();

        $recentPayments = PaymentTransaction::query()
            ->latest()
            ->limit(8)
            ->get();

        $byGateway = PaymentTransaction::query()
            ->where('status', 'paid')
            ->get()
            ->groupBy('gateway')
            ->map(fn ($group) => (float) $group->sum('amount'))
            ->sortDesc();

        return view('admin.dashboard', [
            'revenue' => $revenue,
            'revenueThisMonth' => $revenueThisMonth,
            'users' => $users,
            'newUsers' => $newUsers,
            'activeSubscriptions' => $activeSubscriptions,
            'audits' => $audits,
            'documents' => $documents,
            'words' => $words,
            'chart' => $chart,
            'topAudits' => $topAudits,
            'recentPayments' => $recentPayments,
            'byGateway' => $byGateway,
            'plans' => Plan::query()->where('is_active', true)->count(),
            'gateways' => $gateways->enabled()->count(),
            'readyGateways' => $gateways->ready()->count(),
            'failedAudits' => Audit::query()->where('status', 'failed')->count(),
            'suspendedUsers' => User::query()->where('status', 'suspended')->count(),
        ]);
    }
}
'''

FILES['UserController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->withCount(['audits', 'referrals']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('referral_code', 'like', '%'.$search.'%'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function show(Request $request, User $user): View
    {
        $user->loadCount(['audits', 'referrals', 'documents']);

        return view('admin.users.show', [
            'user' => $user,
            'audits' => Audit::query()->where('user_id', $user->id)->latest()->limit(10)->get(),
            'invoices' => Invoice::query()->where('user_id', $user->id)->latest()->limit(10)->get(),
            'subscriptions' => Subscription::query()->where('user_id', $user->id)->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:80'],
            'currency' => ['nullable', 'string', 'in:USD,BDT'],
            'role' => ['required', 'string', 'in:user,admin'],
            'status' => ['required', 'string', 'in:active,suspended'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('error', 'You cannot remove your own administrator access.');
        }

        $user->fill($data)->save();

        return back()->with('status', 'Account updated.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot suspend your own account.');
        }

        $suspend = $request->boolean('suspend');
        $user->update(['status' => $suspend ? User::STATUS_SUSPENDED : User::STATUS_ACTIVE]);

        return back()->with('status', $suspend ? 'Account suspended.' : 'Account restored.');
    }

    public function credits(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:-10000', 'max:10000'],
            'reason' => ['nullable', 'string', 'max:180'],
        ]);

        $amount = (int) round((float) $data['amount']);

        if ($amount === 0) {
            return back()->with('error', 'Enter a non zero amount.');
        }

        $user->grantCredits($amount, $amount > 0 ? 'bonus' : 'adjustment', $data['reason'] ?: 'Manual adjustment by an administrator');

        return back()->with('status', 'Credits updated. New balance: '.number_format((float) $user->fresh()->credits_balance, 0).'.');
    }

    public function password(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user->password = $data['password'];
        $user->save();

        return back()->with('status', 'Password reset for '.$user->email.'.');
    }
}
'''

FILES['PlanController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['features'] = $this->features($request);

        Plan::create($data);

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validated($request, $plan);

        $data['features'] = $this->features($request);

        $plan->update($data);

        return back()->with('status', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return back()->with('status', 'Plan deleted.');
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:600'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'price_monthly_bdt' => ['required', 'numeric', 'min:0'],
            'price_yearly_bdt' => ['required', 'numeric', 'min:0'],
            'credits' => ['required', 'integer', 'min:0', 'max:100000'],
            'max_sites' => ['required', 'integer', 'min:1', 'max:10000'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function features(Request $request): array
    {
        $features = $request->input('features', []);

        if (is_string($features)) {
            $features = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $features) ?: []));
        }

        return array_values(array_filter((array) $features, fn ($feature) => filled($feature)));
    }

    private function uniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'plan';
        $slug = $base;
        $i = 2;

        while (Plan::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
'''

FILES['GatewayController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gateway;
use App\Services\Billing\GatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayController extends Controller
{
    public function index(Request $request, GatewayManager $manager): View
    {
        $gateways = Gateway::query()->orderBy('sort_order')->get();

        $rows = $gateways->map(function (Gateway $gateway) use ($manager) {
            $driver = $manager->driver($gateway);

            return [
                'gateway' => $gateway,
                'configured' => $driver?->isConfigured() ?? false,
                'supported' => $driver !== null,
            ];
        });

        return view('admin.gateways.index', [
            'rows' => $rows,
        ]);
    }

    public function update(Request $request, Gateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:240'],
            'logo' => ['nullable', 'string', 'max:120'],
            'currencies' => ['nullable', 'string', 'max:120'],
            'countries' => ['nullable', 'string', 'max:240'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_test_mode' => ['nullable', 'boolean'],
        ]);

        $credentials = $request->input('credentials', []);

        $gateway->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? null,
            'currencies' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['currencies'] ?? ''))))),
            'countries' => array_values(array_filter(array_map('trim', explode(',', (string) ($data['countries'] ?? ''))))),
            'sort_order' => (int) $data['sort_order'],
            'is_test_mode' => $request->boolean('is_test_mode'),
        ]);

        if (is_array($credentials)) {
            $existing = $gateway->credentials ?? [];

            foreach ($credentials as $key => $value) {
                $key = (string) $key;

                if ($value === null || $value === '') {
                    unset($existing[$key]);

                    continue;
                }

                $existing[$key] = (string) $value;
            }

            $gateway->credentials = $existing;
        }

        $gateway->save();

        return back()->with('status', 'Gateway '.$gateway->name.' updated.');
    }

    public function toggle(Gateway $gateway): RedirectResponse
    {
        $gateway->update(['is_enabled' => ! $gateway->is_enabled]);

        return back()->with('status', $gateway->name.' is now '.($gateway->is_enabled ? 'enabled' : 'paused').'.');
    }
}
'''

FILES['InvoiceController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Invoice::query()->with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('number', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', '%'.$search.'%')));
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load('user', 'subscription.plan', 'payments');

        return view('admin.invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    public function markPaid(Request $request, Invoice $invoice, SubscriptionService $service): RedirectResponse
    {
        if ($invoice->isPaid()) {
            return back()->with('error', 'This invoice is already paid.');
        }

        $service->completePayment($invoice, 'manual', 'manual-'.now()->format('YmdHis'), ['confirmed_by' => $request->user()->id]);

        return back()->with('status', 'Invoice marked as paid and the subscription was activated.');
    }
}
'''

FILES['CouponController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::query()->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Coupon::create($data + ['used_count' => 0]);

        return back()->with('status', 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request, $coupon);

        $coupon->update($data);

        return back()->with('status', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('status', 'Coupon deleted.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', $coupon ? 'unique:coupons,code,'.$coupon->id : 'unique:coupons,code'],
            'type' => ['required', 'string', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $data['max_uses'] = $data['max_uses'] ?? null;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['expires_at'] = $data['expires_at'] ?? null;

        return $data;
    }
}
'''

FILES['AuditController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = Audit::query()->with('user');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('domain', 'like', '%'.$search.'%')
                ->orWhere('url', 'like', '%'.$search.'%'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->string('mode')->toString());
        }

        $audits = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.audits.index', [
            'audits' => $audits,
        ]);
    }

    public function show(Audit $audit): View
    {
        $audit->load('user', 'findings');

        return view('admin.audits.show', [
            'audit' => $audit,
            'categories' => $audit->categoryScores(),
        ]);
    }
}
'''

FILES['BlogPostController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(): View
    {
        return view('admin.blog.index', [
            'posts' => BlogPost::query()->with('author')->orderByDesc('created_at')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.edit', [
            'post' => new BlogPost,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = BlogPost::uniqueSlug($data['title']);
        $data['author_id'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;

        BlogPost::create($data);

        return redirect()->route('admin.blog.index')->with('status', 'Post created.');
    }

    public function edit(BlogPost $post): View
    {
        return view('admin.blog.edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = BlogPost::uniqueSlug($data['title'], $post->id);
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? $post->published_at ?? now()) : null;

        $post->update($data);

        return redirect()->route('admin.blog.index')->with('status', 'Post updated.');
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $post->delete();

        return back()->with('status', 'Post deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:400'],
            'tags' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
        ]);

        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', (string) ($data['tags'] ?? '')))));
        $data['published_at'] = $data['published_at'] ?? null;

        return $data;
    }
}
'''

FILES['PageController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.edit', [
            'page' => new Page,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['updated_by'] = $request->user()->id;

        Page::create($data);

        return redirect()->route('admin.pages.index')->with('status', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title'], $page->id);
        $data['updated_by'] = $request->user()->id;

        $page->update($data);

        return redirect()->route('admin.pages.index')->with('status', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return back()->with('status', 'Page deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:400'],
            'status' => ['required', 'string', 'in:draft,published'],
        ]);

        return $data;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i = 2;

        while (Page::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
'''

FILES['ContactMessageController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $query = ContactMessage::query();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $messages = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.messages.index', [
            'messages' => $messages,
        ]);
    }

    public function show(ContactMessage $message): View
    {
        $message->update(['status' => 'open']);

        return view('admin.messages.show', [
            'message' => $message,
        ]);
    }

    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:4000'],
        ]);

        $message->update([
            'reply' => $data['reply'],
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        if ($message->user_id) {
            Notification::create([
                'user_id' => $message->user_id,
                'type' => 'contact',
                'title' => 'We replied to: '.$message->subject,
                'body' => Str::limit(strip_tags($data['reply']), 140),
                'action_url' => route('support.index'),
            ]);
        }

        return back()->with('status', 'Reply saved.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('status', 'Message deleted.');
    }
}
'''

FILES['ChatController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = ChatConversation::query()
            ->with('user')
            ->withCount(['messages'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('admin.chat.index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(ChatConversation $conversation): View
    {
        $conversation->load('user');

        $conversation->messages()
            ->where('sender_type', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('admin.chat.show', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->latest()->get()->reverse(),
        ]);
    }

    public function reply(Request $request, ChatConversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->user()->id,
            'sender_type' => 'admin',
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        Notification::create([
            'user_id' => $conversation->user_id,
            'type' => 'chat',
            'title' => 'Support replied to your message',
            'body' => Str::limit($data['body'], 140),
            'action_url' => route('support.index', ['conversation' => $conversation->id]),
        ]);

        return back()->with('status', 'Reply sent.');
    }
}
'''

FILES['AffiliateController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEarning;
use App\Models\AffiliatePayout;
use App\Models\Setting;
use App\Models\User;
use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffiliateController extends Controller
{
    public function index(Request $request): View
    {
        $payouts = AffiliatePayout::query()
            ->with('user')
            ->latest()
            ->paginate(20);

        $earnings = AffiliateEarning::query()
            ->with('user')
            ->latest()
            ->limit(20)
            ->get();

        $top = User::query()
            ->withCount(['earnings', 'referrals'])
            ->having('earnings_count', '>', 0)
            ->orderByDesc('earnings_count')
            ->limit(10)
            ->get();

        return view('admin.affiliate.index', [
            'payouts' => $payouts,
            'earnings' => $earnings,
            'top' => $top,
            'rate' => AffiliateService::commissionRate(),
            'minimum' => AffiliateService::payoutMinimum(),
            'totalOwed' => (float) AffiliateEarning::query()->whereIn('status', ['pending', 'approved'])->sum('amount'),
        ]);
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payout_minimum' => ['required', 'numeric', 'min:0'],
            'cookie_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        Setting::put('affiliate.commission_rate', (float) $data['commission_rate']);
        Setting::put('affiliate.payout_minimum', (float) $data['payout_minimum']);
        Setting::put('affiliate.cookie_days', (int) $data['cookie_days']);

        return back()->with('status', 'Affiliate settings saved.');
    }

    public function payout(AffiliatePayout $payout): View
    {
        $payout->load('user');

        return view('admin.affiliate.payout', [
            'payout' => $payout,
        ]);
    }

    public function updatePayout(Request $request, AffiliatePayout $payout): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,paid,rejected'],
            'admin_note' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payout->update($data);

        if ($data['status'] === 'paid' && ! $payout->processed_at) {
            $payout->update(['processed_at' => now()]);

            AffiliateEarning::query()
                ->where('user_id', $payout->user_id)
                ->whereIn('status', ['approved', 'pending'])
                ->update(['status' => 'paid', 'paid_at' => now()]);
        }

        if ($data['status'] === 'rejected') {
            $payout->user()->increment('wallet_balance', (float) $payout->amount);
        }

        return back()->with('status', 'Withdrawal updated.');
    }
}
'''

FILES['SettingController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * The settings that are safe to show in a plain text field.
     */
    public const EDITABLE = [
        'ai.api_key' => ['label' => 'OpenRouter API key', 'type' => 'secret', 'group' => 'ai'],
        'ai.base_url' => ['label' => 'OpenRouter base URL', 'type' => 'text', 'group' => 'ai'],
        'ai.site_url' => ['label' => 'Site URL sent to OpenRouter', 'type' => 'text', 'group' => 'ai'],
        'ai.site_name' => ['label' => 'Application name sent to OpenRouter', 'type' => 'text', 'group' => 'ai'],
        'ai.price_per_million' => ['label' => 'Assumed price per million tokens', 'type' => 'number', 'group' => 'ai'],
        'ai.model_normal' => ['label' => 'Model for the Normal audit', 'type' => 'text', 'group' => 'ai'],
        'ai.model_standard' => ['label' => 'Model for the Standard audit', 'type' => 'text', 'group' => 'ai'],
        'ai.model_premium' => ['label' => 'Model for the Premium audit', 'type' => 'text', 'group' => 'ai'],
        'ai.model_writer_standard' => ['label' => 'Model for the Standard writer', 'type' => 'text', 'group' => 'ai'],
        'ai.model_writer_premium' => ['label' => 'Model for the Premium writer', 'type' => 'text', 'group' => 'ai'],
        'indexing.google_service_account' => ['label' => 'Google Indexing API service account JSON', 'type' => 'textarea', 'group' => 'indexing'],
        'indexing.indexnow_key' => ['label' => 'IndexNow API key', 'type' => 'secret', 'group' => 'indexing'],
        'indexing.indexnow_key_location' => ['label' => 'IndexNow key file URL', 'type' => 'text', 'group' => 'indexing'],
        'currency.primary' => ['label' => 'Primary currency', 'type' => 'text', 'group' => 'currency'],
        'currency.bdt_rate' => ['label' => 'Taka exchange rate', 'type' => 'number', 'group' => 'currency'],
        'billing.free_signup_credits' => ['label' => 'Free credits for new accounts', 'type' => 'number', 'group' => 'billing'],
        'billing.credits_price' => ['label' => 'Price for one credit', 'type' => 'number', 'group' => 'billing'],
        'support_email' => ['label' => 'Support email address', 'type' => 'text', 'group' => 'general'],
        'contact_address' => ['label' => 'Company address', 'type' => 'text', 'group' => 'general'],
        'crawl_max_pages' => ['label' => 'Hard cap on crawled pages', 'type' => 'number', 'group' => 'general'],
    ];

    public function index(): View
    {
        $settings = [];

        foreach (self::EDITABLE as $key => $meta) {
            $settings[$key] = [
                'meta' => $meta,
                'value' => Setting::get($key),
            ];
        }

        return view('admin.settings.index', [
            'settings' => $settings,
            'groups' => [
                'ai' => 'Artificial intelligence',
                'indexing' => 'Indexing',
                'currency' => 'Currency',
                'billing' => 'Billing',
                'general' => 'General',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['nullable', 'array'],
        ]);

        foreach ((array) ($data['settings'] ?? []) as $key => $value) {
            if (! array_key_exists($key, self::EDITABLE)) {
                continue;
            }

            if (self::EDITABLE[$key]['type'] === 'secret' && $value === '') {
                continue;
            }

            Setting::put($key, $value, [
                'group' => self::EDITABLE[$key]['group'],
                'label' => self::EDITABLE[$key]['label'],
                'type' => self::EDITABLE[$key]['type'],
            ]);
        }

        return back()->with('status', 'Settings saved.');
    }

    public function updateSecret(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120'],
            'value' => ['nullable', 'string', 'max:20000'],
        ]);

        Setting::put($data['key'], $data['value']);

        return back()->with('status', 'Stored.');
    }
}
'''

FILES['PromptController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromptController extends Controller
{
    public const KEYS = [
        'prompt.audit_system' => 'Audit system prompt',
        'prompt.audit_user' => 'Audit user prompt',
        'prompt.writer_system' => 'Writer system prompt',
        'prompt.writer_user' => 'Writer user prompt',
    ];

    public function index(): View
    {
        $prompts = [];

        foreach (self::KEYS as $key => $label) {
            $prompts[$key] = [
                'label' => $label,
                'value' => Setting::get($key) ?: '',
            ];
        }

        return view('admin.prompts.index', [
            'prompts' => $prompts,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'prompts' => ['nullable', 'array'],
        ]);

        foreach ((array) ($data['prompts'] ?? []) as $key => $value) {
            if (! array_key_exists($key, self::KEYS)) {
                continue;
            }

            Setting::put($key, (string) $value, [
                'group' => 'prompts',
                'label' => self::KEYS[$key],
                'type' => 'textarea',
            ]);
        }

        return back()->with('status', 'Prompts updated.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string'],
        ]);

        if (! array_key_exists($data['key'], self::KEYS)) {
            return back()->with('error', 'Unknown prompt.');
        }

        Setting::put($data['key'], null, [
            'group' => 'prompts',
            'label' => self::KEYS[$data['key']],
            'type' => 'textarea',
        ]);

        return back()->with('status', 'Prompt reset to the built in version.');
    }
}
'''

FILES['LogController.php'] = r'''<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($q) => $q
                ->where('action', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', '%'.$search.'%')));
        }

        $logs = $query->orderByDesc('id')->paginate(30)->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
        ]);
    }
}
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} admin controllers")
