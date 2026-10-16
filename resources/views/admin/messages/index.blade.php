
@extends('layouts.admin')

@section('title', 'Messages')
@section('heading', 'Contact messages')
@section('subheading', 'Everything sent through the public contact form')

@section('content')

<div class="panel mb-4">
    <form method="GET" class="grid filters">
        <div>
            <label class="label" for="status">Status</label>
            <select class="field" id="status" name="status">
                <option value="">Any</option>
                @foreach (['new', 'open', 'replied', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div></div>
        <div>
            <button class="btn btn-primary btn-block btn-sm" type="submit">Filter</button>
        </div>
    </form>
</div>

<div class="panel">
    @if ($messages->isEmpty())
        <p class="muted">No messages yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead><tr><th>From</th><th>Reason</th><th>Subject</th><th>Status</th><th>Received</th><th></th></tr></thead>
                <tbody>
                    @foreach ($messages as $message)
                        <tr>
                            <td>
                                <span class="font-semibold">{{ $message->name }}</span>
                                <div class="faint text-xs">{{ $message->email }}</div>
                            </td>
                            <td class="text-sm muted">{{ ucfirst(str_replace('_', ' ', $message->category)) }}</td>
                            <td class="text-sm">{{ $message->subject }}</td>
                            <td>
                                <span class="badge {{ $message->status === 'replied' ? 'badge-pass' : ($message->status === 'new' ? 'badge-info' : 'badge-muted') }}">
                                    {{ ucfirst($message->status) }}
                                </span>
                            </td>
                            <td class="text-sm muted">{{ $message->created_at->diffForHumans() }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.messages.show', $message) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $messages->links() }}</div>
    @endif
</div>

@endsection
