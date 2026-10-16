<?php

namespace App\Http\Controllers;

use App\Models\ContentDocument;
use App\Models\Setting;
use App\Services\Content\ContentExporter;
use App\Services\Content\ContentWriterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
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

        if (! $user->spendCredits($credits, 'Article: '.$data['keyword'], 'content_document')) {
            return back()->withInput()->with('error', 'This article needs '.$credits.' credits.');
        }

        try {
            $document = app(ContentWriterService::class)->generate($user, $data);
        } catch (\Throwable $exception) {
            $user->grantCredits($credits, 'refund', 'Refund for a writer run that did not finish');

            return back()->withInput()->with('error', $exception->getMessage());
        }

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
