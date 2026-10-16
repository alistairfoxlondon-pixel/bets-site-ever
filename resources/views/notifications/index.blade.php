
@extends('layouts.app')

@section('title', 'Notifications')
@section('heading', 'Notifications')
@section('subheading', 'Audit results, payment updates and support replies')

@section('actions')
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-ghost btn-sm" type="submit">Mark all as read</button>
    </form>
@endsection

@section('content')

<div class="panel">
    @if ($notifications->isEmpty())
        <p class="muted">Nothing here yet.</p>
    @else
        <ul class="space-y-2">
            @foreach ($notifications as $notification)
                <li class="card-flat" style="padding:.85rem 1rem;{{ $notification->isRead() ? 'opacity:.68;' : 'border-color:var(--color-brand-line);' }}">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div class="min-w-0">
                            <strong>{{ $notification->title }}</strong>
                            <p class="muted text-sm mt-1">{{ $notification->body }}</p>
                            <p class="faint text-xs mt-1 mono">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification) }}">
                            @csrf
                            <button class="btn btn-quiet btn-sm" type="submit">
                                {{ $notification->isRead() ? 'Open' : 'Mark as read' }}
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>

@endsection
