
@extends('layouts.app')

@section('title', 'Indexing')
@section('heading', 'Quick indexing')
@section('subheading', 'Push a URL to Google and to the IndexNow network')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Submit a URL</h2></div>

        <form method="POST" action="{{ route('indexing.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="url">Page address</label>
                <input class="field" id="url" name="url" type="url" placeholder="https://example.com/blog/my-article" required>
            </div>

            <div>
                <label class="label">Engines</label>
                <div class="space-y-2">
                    @foreach ($engines as $slug => $label)
                        <label class="card-flat" style="padding:.7rem .9rem;cursor:pointer;display:block;">
                            <span class="flex items-center gap-2.5">
                                <input type="checkbox" name="engines[]" value="{{ $slug }}" @checked($slug === 'indexnow')>
                                <span class="text-sm">{{ $label }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit">Send for indexing</button>

            <p class="hint">
                The Google Indexing API expects a service account. The administrator configures it in
                the control panel under Settings.
            </p>
        </form>
    </div>

    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Submission history</h2></div>

        @if ($submissions->isEmpty())
            <p class="muted text-sm">Nothing submitted yet.</p>
        @else
            <div class="table-wrap">
                <table class="table-app">
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Engine</th>
                            <th>Result</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td class="truncate" style="max-width:200px;">{{ $submission->url }}</td>
                                <td class="text-sm muted">{{ $engines[$submission->engine] ?? $submission->engine }}</td>
                                <td>
                                    <span class="badge {{ $submission->status === 'success' ? 'badge-pass' : 'badge-fail' }}">
                                        {{ $submission->status === 'success' ? 'Sent' : 'Failed' }}
                                    </span>
                                </td>
                                <td class="text-sm muted">{{ $submission->submitted_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $submissions->links() }}</div>
        @endif
    </div>
</div>

@endsection
