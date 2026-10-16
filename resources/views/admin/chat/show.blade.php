
@extends('layouts.admin')

@section('title', 'Chat with '.$conversation->user?->name)
@section('heading', 'Chat with '.($conversation->user?->name ?? 'customer'))
@section('subheading', $conversation->subject)

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Conversation</h2></div>

        <div class="space-y-3" style="max-height:60vh;overflow:auto;">
            @foreach ($messages as $message)
                <div style="display:flex;justify-content:{{ $message->sender_type === 'admin' ? 'flex-end' : 'flex-start' }};">
                    <div class="card-flat" style="padding:.7rem .9rem;max-width:80%;background:{{ $message->sender_type === 'admin' ? 'var(--color-brand-soft)' : 'var(--color-paper)' }};">
                        <p class="text-sm" style="white-space:pre-line;">{{ $message->body }}</p>
                        <p class="faint text-xs mt-1">
                            {{ $message->sender_type === 'admin' ? 'Support' : ($message->sender?->name ?? 'Customer') }}
                            &middot; {{ $message->created_at->format('j M, H:i') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Reply</h2>
        <form method="POST" action="{{ route('admin.chat.reply', $conversation) }}" class="space-y-3">
            @csrf
            <textarea class="field" name="body" rows="8" required placeholder="Write the reply here."></textarea>
            <button class="btn btn-primary btn-block" type="submit">Send the reply</button>
        </form>

        <div class="rule my-4"></div>

        <h3 class="text-lg mb-2">Customer</h3>
        <dl class="space-y-2" style="font-size:.92rem;">
            <div class="flex justify-between"><dt class="muted">Email</dt><dd>{{ $conversation->user?->email }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Plan</dt><dd>{{ $conversation->user?->activeSubscription?->plan?->name ?? 'free' }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Credits</dt><dd class="mono">{{ number_format((float) ($conversation->user?->credits_balance ?? 0), 0) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Joined</dt><dd>{{ $conversation->user?->created_at->format('j M Y') }}</dd></div>
        </dl>
    </div>
</div>

@endsection
