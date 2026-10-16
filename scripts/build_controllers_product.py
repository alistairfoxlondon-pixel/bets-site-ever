#!/usr/bin/env python3
"""Write the product (signed in) controllers."""
import os

ROOT = "/home/user/bets-site-ever/app/Http/Controllers"
FILES = {}

FILES['DashboardController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\ContentDocument;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $audits = Audit::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $documents = ContentDocument::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(3)
            ->get();

        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'pending'])
            ->latest()
            ->first();

        $stats = [
            'audits' => Audit::query()->where('user_id', $user->id)->count(),
            'completed' => Audit::query()->where('user_id', $user->id)->where('status', 'completed')->count(),
            'documents' => ContentDocument::query()->where('user_id', $user->id)->count(),
            'words' => (int) ContentDocument::query()->where('user_id', $user->id)->sum('word_count'),
        ];

        $ready = Audit::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('probability')
            ->orderByDesc('completed_at')
            ->first();

        $modes = collect(config('adready.modes'))
            ->map(fn (array $mode, string $key) => [
                'key' => $key,
                'label' => $mode['label'],
                'blurb' => $mode['blurb'],
                'credits' => $mode['credits'],
                'pages' => $mode['max_pages'],
            ])
            ->values();

        return view('dashboard', [
            'audits' => $audits,
            'documents' => $documents,
            'subscription' => $subscription,
            'stats' => $stats,
            'ready' => $ready,
            'modes' => $modes,
        ]);
    }
}
'''

FILES['AuditController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\Setting;
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

        $user->spendCredits($cost, 'Audit of '.$domain, 'audit', $audit->id);

        app(AuditService::class)->run($audit);

        $audit = $audit->fresh();

        if ($audit->status === Audit::STATUS_FAILED) {
            return redirect()->route('audits.show', $audit)
                ->with('error', 'The audit could not finish: '.($audit->error_message ?: 'unknown error'));
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

        $user->spendCredits($cost, 'Re-audit of '.$audit->domain, 'audit', $new->id);

        app(AuditService::class)->run($new);

        return redirect()->route('audits.show', $new->fresh())
            ->with('status', 'Re-audit finished.');
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

        DB::transaction(function () use ($audit, $request) {
            $audit->findings()->delete();
            $audit->delete();

            $refund = (int) data_get($audit->meta, 'credits_cost', 0);

            if ($refund > 0) {
                $request->user()->grantCredits($refund, 'refund', 'Refund for deleted audit');
            }
        });

        return redirect()->route('audits.index')->with('status', 'Audit deleted and the credits were returned to your balance.');
    }

    private function authorizeAudit(Request $request, Audit $audit): void
    {
        abort_unless(
            $audit->user_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );
    }
}
'''

FILES['DocumentController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\ContentDocument;
use App\Models\Setting;
use App\Services\Content\ContentWriterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function landing(Request $request): View
    {
        return view('tools.writer', [
            'type' => 'adsense_article',
            'modes' => collect(config('adready.writer.modes'))
                ->map(fn (array $mode, string $key) => [
                    'key' => $key,
                    'label' => $mode['label'],
                    'credits' => $mode['credits'],
                    'words' => $key === 'premium' ? '2200 to 3000 words' : '2000 to 2600 words',
                ])
                ->values(),
        ]);
    }

    public function affiliateLanding(Request $request): View
    {
        return view('tools.writer', [
            'type' => 'affiliate_article',
            'modes' => collect(config('adready.writer.modes'))
                ->map(fn (array $mode, string $key) => [
                    'key' => $key,
                    'label' => $mode['label'],
                    'credits' => $mode['credits'],
                    'words' => $key === 'premium' ? '2200 to 3000 words' : '2000 to 2600 words',
                ])
                ->values(),
        ]);
    }

    public function index(Request $request): View
    {
        $documents = ContentDocument::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('documents.index', [
            'documents' => $documents,
        ]);
    }

    public function create(Request $request): View
    {
        $type = $request->string('type')->toString() === 'affiliate_article'
            ? 'affiliate_article'
            : 'adsense_article';

        return view('documents.create', [
            'type' => $type,
            'modes' => collect(config('adready.writer.modes'))
                ->map(fn (array $mode, string $key) => [
                    'key' => $key,
                    'label' => $mode['label'],
                    'credits' => $mode['credits'],
                ])
                ->values(),
            'keyword' => $request->string('keyword')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', 'in:adsense_article,affiliate_article'],
            'keyword' => ['required', 'string', 'min:3', 'max:180'],
            'mode' => ['required', 'string', 'in:standard,premium'],
            'url' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:40'],
            'tone' => ['nullable', 'string', 'max:120'],
            'product' => ['nullable', 'string', 'max:180'],
            'site_context' => ['nullable', 'string', 'max:1000'],
            'extra_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $credits = (int) config('adready.writer.modes.'.$data['mode'].'.credits');

        if ((float) $user->credits_balance < $credits) {
            return back()->withInput()->with('error', 'This article needs '.$credits.' credits. You have '.number_format((float) $user->credits_balance, 0).'.');
        }

        $document = app(ContentWriterService::class)->generate($user, $data);

        $user->spendCredits($credits, 'Article: '.$document->title, 'content_document', $document->id);

        return redirect()->route('documents.show', $document)
            ->with('status', 'Your article is ready to publish.');
    }

    public function show(Request $request, ContentDocument $document): View
    {
        abort_unless($document->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return view('documents.show', [
            'document' => $document,
            'faq' => $document->faq ?? [],
        ]);
    }

    public function export(Request $request, ContentDocument $document, string $format): Response
    {
        abort_unless($document->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $exporter = app(ContentExporter::class);
        $result = $exporter->export($document, $format);

        return response($result['body'], 200, [
            'Content-Type' => $result['content_type'],
            'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
            'Content-Length' => (string) strlen($result['body']),
        ]);
    }

    public function destroy(Request $request, ContentDocument $document): RedirectResponse
    {
        abort_unless($document->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $document->delete();

        return redirect()->route('documents.index')->with('status', 'Article deleted.');
    }
}
'''

FILES['IndexingController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\IndexingSubmission;
use App\Services\Indexing\IndexingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class IndexingController extends Controller
{
    public function landing(Request $request): View
    {
        return view('tools.indexing', [
            'engines' => IndexingService::ENGINES,
        ]);
    }

    public function index(Request $request): View
    {
        $submissions = IndexingSubmission::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('indexing.index', [
            'submissions' => $submissions,
            'engines' => IndexingService::ENGINES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url' => ['required', 'string', 'url', 'max:500'],
            'engines' => ['required', 'array', 'min:1'],
            'engines.*' => ['string', 'in:google,bing,indexnow'],
        ]);

        $url = trim($data['url']);

        $results = app(IndexingService::class)->submit($request->user(), $url, $data['engines']);

        $failed = collect($results)->filter(fn (array $result) => $result['status'] !== 'success');

        if ($failed->isEmpty()) {
            return redirect()->route('indexing.index')->with('status', 'The URL was sent to every selected engine.');
        }

        return redirect()->route('indexing.index')
            ->with('error', $failed->first()['message'] ?? 'Some submissions failed.');
    }

    public function keyFile(Request $request, string $key): Response
    {
        $configured = app(IndexingService::class)->indexNowKey();

        if ($configured === '' || ! hash_equals($configured, $key)) {
            abort(404);
        }

        return response($key, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
'''

FILES['NotificationController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return redirect($notification->action_url ?: route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        Notification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
'''

FILES['SupportController.php'] = r'''<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $conversation = $this->currentConversation($request);

        return view('support.index', [
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->latest()->get()->reverse() ?? collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $user = $request->user();

        $conversation = $this->currentConversation($request);

        if (! $conversation) {
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'subject' => $data['subject'] ?? 'Support request',
                'status' => 'open',
                'last_message_at' => now(),
            ]);
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'sender_type' => 'user',
            'body' => $data['body'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        foreach (\App\Models\User::where('role', 'admin')->get() as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'chat',
                'title' => 'New support message from '.$user->name,
                'body' => Str::limit($data['body'], 140),
                'action_url' => route('admin.chat.show', $conversation),
            ]);
        }

        return back()->with('status', 'Message sent. We reply inside the app.');
    }

    public function messages(Request $request): View
    {
        $conversation = $this->currentConversation($request);

        return view('support.messages', [
            'conversation' => $conversation,
            'messages' => $conversation?->messages()->latest()->get()->reverse() ?? collect(),
        ]);
    }

    private function currentConversation(Request $request): ?ChatConversation
    {
        $conversationId = $request->integer('conversation');

        if ($conversationId > 0) {
            $conversation = ChatConversation::query()
                ->where('user_id', $request->user()->id)
                ->find($conversationId);

            if ($conversation) {
                $conversation->messages()
                    ->where('sender_type', 'admin')
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

                return $conversation;
            }
        }

        $conversation = ChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if ($conversation) {
            $conversation->messages()
                ->where('sender_type', 'admin')
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return $conversation;
    }
}
'''

for name, content in FILES.items():
    path = os.path.join(ROOT, name)
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as fh:
        fh.write(content)

print(f"wrote {len(FILES)} product controllers")
