<?php

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
