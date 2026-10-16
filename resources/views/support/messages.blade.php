@extends('layouts.app')

@section('title', 'Conversations')
@section('heading', 'Conversations')
@section('subheading', 'Every support thread you have opened with us')

@section('content')

<div class="grid split" id="support-layout">
    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">Threads</h2>
            <a href="{{ route('support.index') }}" class="text-xs link">New message</a>
        </div>

        @if ($conversations->isEmpty())
            <p class="muted text-sm">You have not opened a thread yet.</p>
        @else
            <ul class="space-y-2">
                @foreach ($conversations as $thread)
                    @php $active = $conversation && $thread->id === $conversation->id; @endphp
                    <li>
                        <a href="{{ route('support.messages', ['conversation' => $thread->id]) }}"
                           class="card-flat" style="display:block;padding:.7rem .8rem;{{ $active ? 'border-color:var(--color-brand);' : '' }}">
                            <p class="text-sm" style="font-weight:600;">{{ $thread->subject ?: 'Support request' }}</p>
                            <p class="faint text-xs mt-1">
                                {{ $thread->last_message_at ? $thread->last_message_at->format('j M Y, H:i') : $thread->created_at->format('j M Y') }}
                                &middot; {{ $thread->status === 'open' ? 'Open' : 'Closed' }}
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="panel">
        <div class="panel-head">
            <h2 class="text-lg">{{ $conversation?->subject ?: 'No thread selected' }}</h2>
            @if ($conversation)
                <span class="chip">{{ $conversation->status === 'open' ? 'Open' : 'Closed' }}</span>
            @endif
        </div>

        @if (! $conversation || $messages->isEmpty())
            <p class="muted text-sm">Pick a thread on the left or start a new conversation.</p>
        @else
            <div class="space-y-3" style="max-height:58vh;overflow:auto;">
                @foreach ($messages as $message)
                    <div style="display:flex;justify-content:{{ $message->sender_type === 'user' ? 'flex-end' : 'flex-start' }};">
                        <div class="card-flat" style="padding:.7rem .9rem;max-width:80%;background:{{ $message->sender_type === 'user' ? 'var(--color-brand-soft)' : 'var(--color-paper)' }};">
                            <p class="text-sm" style="white-space:pre-line;">{{ $message->body }}</p>
                            <p class="faint text-xs mt-1">
                                {{ $message->sender_type === 'user' ? 'You' : 'Support' }} &middot; {{ $message->created_at->format('j M, H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rule my-5"></div>

            <form method="POST" action="{{ route('support.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="subject" value="{{ $conversation->subject }}">
                <div>
                    <label class="label" for="body">Reply</label>
                    <textarea class="field" id="body" name="body" rows="4" required placeholder="Write your reply.">{{ old('body') }}</textarea>
                </div>
                <button class="btn btn-primary" type="submit">Send reply</button>
            </form>
        @endif
    </div>
</div>

@endsection
