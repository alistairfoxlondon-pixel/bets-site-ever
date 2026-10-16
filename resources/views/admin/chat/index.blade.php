
@extends('layouts.admin')

@section('title', 'Live chat')
@section('heading', 'Live chat')
@section('subheading', 'Conversations with signed in customers')

@section('content')

<div class="panel">
    @if ($conversations->isEmpty())
        <p class="muted">No conversations yet.</p>
    @else
        <div class="table-wrap">
            <table class="table-app">
                <thead><tr><th>Customer</th><th>Subject</th><th>Messages</th><th>Status</th><th>Last message</th><th></th></tr></thead>
                <tbody>
                    @foreach ($conversations as $conversation)
                        <tr>
                            <td>
                                <span class="font-semibold">{{ $conversation->user?->name }}</span>
                                <div class="faint text-xs">{{ $conversation->user?->email }}</div>
                            </td>
                            <td class="text-sm">{{ $conversation->subject }}</td>
                            <td class="num muted">{{ $conversation->messages_count }}</td>
                            <td>
                                <span class="badge {{ $conversation->status === 'open' ? 'badge-info' : 'badge-muted' }}">{{ ucfirst($conversation->status) }}</span>
                            </td>
                            <td class="text-sm muted">{{ $conversation->last_message_at?->diffForHumans() ?? 'never' }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.chat.show', $conversation) }}" class="btn btn-quiet btn-sm">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $conversations->links() }}</div>
    @endif
</div>

@endsection
