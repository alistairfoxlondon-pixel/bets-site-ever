
@extends('layouts.app')

@section('title', 'Compare audits')
@section('heading', 'Compare runs')
@section('subheading', 'See what changed between two audits of the same site')

@section('content')

@if (! $left || ! $right)
    <div class="panel text-center py-10">
        <p class="muted mb-4">You need two completed audits to compare.</p>
        <a href="{{ route('audits.index') }}" class="btn btn-ghost btn-sm">Back to audits</a>
    </div>
@else
    <div class="panel mb-4">
        <form method="GET" class="grid grid-2" style="gap:1rem;">
            <div>
                <label class="label" for="left">Earlier run</label>
                <select class="field" id="left" name="left">
                    @foreach ($audits as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $left->id)>
                            {{ $option->domain }} &middot; {{ $option->completed_at?->format('j M Y, H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="right">Later run</label>
                <select class="field" id="right" name="right">
                    @foreach ($audits as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $right->id)>
                            {{ $option->domain }} &middot; {{ $option->completed_at?->format('j M Y, H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <button class="btn btn-primary btn-sm" type="submit">Compare</button>
            </div>
        </form>
    </div>

    <p class="muted text-sm mb-4">Readiness is an internal heuristic from sampled public pages, not a prediction of network approval. Compare like-for-like crawl settings where possible.</p>
    @if (data_get($left->meta, 'sample_data') || data_get($right->meta, 'sample_data'))
        <div class="panel mb-4" role="note">At least one run is seeded demonstration data; illustrative values are not results from a real site.</div>
    @endif

    <div class="grid grid-2" style="gap:1.25rem;align-items:start;">
        <div class="panel">
            <p class="label">{{ $left->completed_at?->format('j M Y') }}</p>
            <p class="display" style="font-size:2.6rem;color:var(--color-ink-soft);">{{ $left->score }}<span class="muted" style="font-size:1rem;">/100</span></p>
            <p class="muted text-sm mb-4">{{ $left->verdictLabel() }}</p>
            <div class="space-y-3">
                @foreach ($leftCategories as $key => $category)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ $category['label'] }}</span>
                            <span class="mono muted">{{ $category['score'] === null ? 'Not assessed' : (int) round($category['score']) }}</span>
                        </div>
                        @if ($category['score'] !== null)
                            <div class="meter"><span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <p class="label">{{ $right->completed_at?->format('j M Y') }}</p>
            <p class="display" style="font-size:2.6rem;color:var(--color-brand);">{{ $right->score }}<span class="muted" style="font-size:1rem;">/100</span></p>
            <p class="muted text-sm mb-4">{{ $right->verdictLabel() }}</p>
            <div class="space-y-3">
                @foreach ($rightCategories as $key => $category)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span>{{ $category['label'] }}</span>
                            <span class="mono muted">{{ $category['score'] === null ? 'Not assessed' : (int) round($category['score']) }}</span>
                        </div>
                        @if ($category['score'] !== null)
                            <div class="meter"><span style="width:{{ max(2, min(100, (int) round($category['score']))) }}%;"></span></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-head">
            <h2 class="text-lg">Movement</h2>
            <div class="flex gap-2">
                <a href="{{ route('audits.report', $left) }}" class="btn btn-ghost btn-sm">Earlier PDF</a>
                <a href="{{ route('audits.report', $right) }}" class="btn btn-ghost btn-sm">Later PDF</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-app">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Earlier</th>
                        <th>Later</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rightCategories as $key => $category)
                        @php
                            $before = $leftCategories[$key]['score'] ?? null;
                            $change = $before === null || $category['score'] === null
                                ? null
                                : (int) round($category['score'] - $before);
                        @endphp
                        <tr>
                            <td>{{ $category['label'] }}</td>
                            <td class="num muted">{{ $before === null ? 'Not assessed' : (int) round($before) }}</td>
                            <td class="num"><strong>{{ $category['score'] === null ? 'Not assessed' : (int) round($category['score']) }}</strong></td>
                            <td class="num">
                                @if ($change === null)
                                    <span class="badge badge-muted">not assessed</span>
                                @elseif ($change > 0)
                                    <span class="badge badge-pass">+{{ $change }}</span>
                                @elseif ($change < 0)
                                    <span class="badge badge-fail">{{ $change }}</span>
                                @else
                                    <span class="badge badge-muted">no change</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
