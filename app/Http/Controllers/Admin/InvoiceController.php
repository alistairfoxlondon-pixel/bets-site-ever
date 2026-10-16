<?php

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
