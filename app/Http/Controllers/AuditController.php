<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Audit\UrlGuard;
use App\Services\Audit\UnsafeUrlException;
use App\Services\Pdf\ReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function landing(Request $request): View
    {
        return view('tools.audit', [
            'modes' => collect(config('adready.modes'))
                ->map(fn (array $mode, string $key) => [
                    'key' => $key,
                    'label' => $mode['label'],
                    'blurb' => $mode['blurb'],
                    'credits' => $mode['credits'],
                    'pages' => $mode['max_pages'],
                    'featured' => $key === 'standard',
                ])
                ->values(),
            'networks' => config('adready.networks'),
            'url' => $request->string('url')->toString(),
        ]);
    }

    public function index(Request $request): View
    {
        $audits = Audit::query()
            ->where('user_id', $request->user()->id)
            ->with('findings')
            ->latest()
            ->paginate(12);

        return view('audits.index', [
            'audits' => $audits,
        ]);
    }

    public function create(Request $request): View
    {
        return view('audits.create', [
            'modes' => collect(config('adready.modes'))
                ->map(fn (array $mode, string $key) => [
                    'key' => $key,
                    'label' => $mode['label'],
                    'blurb' => $mode['blurb'],
                    'credits' => $mode['credits'],
                    'pages' => $mode['max_pages'],
                    'featured' => $key === 'standard',
                ])
                ->values(),
            'url' => $request->string('url')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'url' => ['required', 'string', 'max:255'],
            'mode' => ['required', 'string', 'in:normal,standard,premium'],
        ]);

        $guard = new UrlGuard;

        try {
            $url = $guard->assertSafe($data['url']);
        } catch (UnsafeUrlException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $mode = $data['mode'];
        $modeConfig = config('adready.modes.'.$mode);
        $cost = (int) $modeConfig['credits'];

        if ((float) $user->credits_balance < $cost) {
            return back()->withInput()->with('error', 'This audit needs '.$cost.' credits. You have '.number_format((float) $user->credits_balance, 0).'. Add credits to continue.');
        }

        $domain = $guard->host($url);

        $existing = Audit::query()
            ->where('user_id', $user->id)
            ->where('normalized_url', $url)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $audit = DB::transaction(function () use ($user, $url, $domain, $mode, $cost, $request): ?Audit {
            $audit = Audit::create([
                'user_id' => $user->id,
                'url' => $url,
                'normalized_url' => $url,
                'domain' => $domain,
                'mode' => $mode,
                'status' => Audit::STATUS_QUEUED,
                'ip' => $request->ip(),
                'meta' => ['credits_cost' => $cost],
            ]);

            if (! $user->spendCredits($cost, 'Audit of '.$domain, 'audit', $audit->id)) {
                $audit->delete();

                return null;
            }

            return $audit;
        });

        if (! $audit) {
            return back()->withInput()->with('error', 'Your credit balance changed. Add credits and try again.');
        }

        app(AuditService::class)->run($audit);

        $audit = $audit->fresh();

        if ($audit->status === Audit::STATUS_FAILED) {
            $this->refundFailedAudit($audit);

            return redirect()->route('audits.show', $audit)
                ->with('error', 'The audit could not finish: '.($audit->error_message ?: 'unknown error').' Your credits have been returned.');
        }

        return redirect()->route('audits.show', $audit)
            ->with('status', $existing
                ? 'Audit complete. Compare it with your previous run to see what changed.'
                : 'Audit complete.');
    }

    public function show(Request $request, Audit $audit): View
    {
        $this->authorizeAudit($request, $audit);

        $audit->load('findings');

        $previous = Audit::query()
            ->where('user_id', $request->user()->id)
            ->where('normalized_url', $audit->normalized_url)
            ->whereKeyNot($audit->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->first();

        $networks = (array) data_get($audit->meta, 'network_readiness', []);

        return view('audits.show', [
            'audit' => $audit,
            'previous' => $previous,
            'networks' => $networks,
            'categories' => $audit->categoryScores(),
            'critical' => $audit->findings->where('severity', AuditFinding::SEVERITY_CRITICAL),
            'warnings' => $audit->findings->where('severity', AuditFinding::SEVERITY_WARNING),
            'passes' => $audit->findings->where('severity', AuditFinding::SEVERITY_PASS),
            'notes' => $audit->findings->where('severity', AuditFinding::SEVERITY_INFO),
            'roadmap' => array_values((array) data_get($audit->meta, 'roadmap', [])),
            'strengths' => array_values((array) data_get($audit->meta, 'strengths', [])),
            'risks' => array_values((array) data_get($audit->meta, 'risks', [])),
            'criticalFixes' => array_values((array) data_get($audit->meta, 'critical_fixes', [])),
            'analysis' => (array) data_get($audit->meta, 'analysis', []),
            'summary' => data_get($audit->meta, 'summary'),
        ]);
    }

    public function compare(Request $request): View
    {
        $audits = Audit::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->get();

        $leftId = (int) $request->query('left');
        $rightId = (int) $request->query('right');

        $left = $audits->firstWhere('id', $leftId) ?? $audits->get(1);
        $right = $audits->firstWhere('id', $rightId) ?? $audits->get(0);

        return view('audits.compare', [
            'audits' => $audits,
            'left' => $left,
            'right' => $right,
            'leftCategories' => $left?->categoryScores() ?? [],
            'rightCategories' => $right?->categoryScores() ?? [],
        ]);
    }

    public function rerun(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorizeAudit($request, $audit);

        $user = $request->user();
        $cost = (int) config('adready.modes.'.$audit->mode.'.credits');

        if ((float) $user->credits_balance < $cost) {
            return back()->with('error', 'A re-audit needs '.$cost.' credits. Add credits to continue.');
        }

        $new = DB::transaction(function () use ($user, $audit, $cost, $request): ?Audit {
            $new = Audit::create([
                'user_id' => $user->id,
                'url' => $audit->url,
                'normalized_url' => $audit->normalized_url,
                'domain' => $audit->domain,
                'mode' => $audit->mode,
                'status' => Audit::STATUS_QUEUED,
                'ip' => $request->ip(),
                'meta' => ['credits_cost' => $cost, 'rerun_of' => $audit->id],
            ]);

            if (! $user->spendCredits($cost, 'Re-audit of '.$audit->domain, 'audit', $new->id)) {
                $new->delete();

                return null;
            }

            return $new;
        });

        if (! $new) {
            return back()->with('error', 'Your credit balance changed. Add credits and try again.');
        }

        app(AuditService::class)->run($new);
        $new = $new->fresh();

        if ($new->status === Audit::STATUS_FAILED) {
            $this->refundFailedAudit($new);

            return redirect()->route('audits.show', $new)
                ->with('error', 'The re-audit could not finish: '.($new->error_message ?: 'unknown error').' Your credits have been returned.');
        }

        return redirect()->route('audits.show', $new)->with('status', 'Re-audit finished.');
    }

    public function report(Request $request, Audit $audit): Response
    {
        $this->authorizeAudit($request, $audit);

        return app(ReportBuilder::class)->download($audit);
    }

    public function preview(Request $request, Audit $audit): Response
    {
        $this->authorizeAudit($request, $audit);

        return app(ReportBuilder::class)->inline($audit);
    }

    public function destroy(Request $request, Audit $audit): RedirectResponse
    {
        $this->authorizeAudit($request, $audit);

        $deleted = DB::transaction(function () use ($audit): bool {
            $locked = Audit::query()->lockForUpdate()->findOrFail($audit->id);

            if (in_array($locked->status, [Audit::STATUS_QUEUED, Audit::STATUS_RUNNING], true)) {
                return false;
            }

            if ($locked->status === Audit::STATUS_FAILED && ! data_get($locked->meta, 'credit_refunded')) {
                $refund = max(0, (int) data_get($locked->meta, 'credits_cost', 0));
                $owner = $refund > 0 ? User::query()->find($locked->user_id) : null;

                if ($owner) {
                    $owner->grantCredits($refund, 'refund', 'Refund for failed audit', 'audit', $locked->id);
                }

                $meta = $locked->meta ?? [];
                $meta['credit_refunded'] = true;
                $locked->meta = $meta;
                $locked->save();
            }

            $locked->findings()->delete();
            $locked->delete();

            return true;
        });

        if (! $deleted) {
            return back()->with('error', 'An audit cannot be deleted while it is queued or running.');
        }

        return redirect()->route('audits.index')->with('status', 'Audit deleted.');
    }

    private function refundFailedAudit(Audit $audit): void
    {
        DB::transaction(function () use ($audit): void {
            $locked = Audit::query()->lockForUpdate()->find($audit->id);

            if (! $locked || $locked->status !== Audit::STATUS_FAILED || data_get($locked->meta, 'credit_refunded')) {
                return;
            }

            $refund = (int) data_get($locked->meta, 'credits_cost', 0);

            if ($refund > 0) {
                $user = User::query()->find($locked->user_id);

                if ($user) {
                    $user->grantCredits($refund, 'refund', 'Refund for failed audit', 'audit', $locked->id);
                }
            }

            $meta = $locked->meta ?? [];
            $meta['credit_refunded'] = true;
            $locked->meta = $meta;
            $locked->save();
        });
    }

    private function authorizeAudit(Request $request, Audit $audit): void
    {
        abort_unless(
            $audit->user_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );
    }
}
