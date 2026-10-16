<?php

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
