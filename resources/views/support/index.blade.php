
@extends('layouts.app')

@section('title', 'Support')
@section('heading', 'Support')
@section('subheading', 'Ask a question and keep the whole thread in one place')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <div class="panel-head"><h2 class="text-lg">Conversation</h2></div>

        @if ($messages->isEmpty())
            <p class="muted text-sm mb-4">No messages yet. Start the conversation on the right.</p>
        @else
            <div class="space-y-3" style="max-height:52vh;overflow:auto;">
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
        @endif
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Send a message</h2>
        <form method="POST" action="{{ route('support.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="label" for="subject">Subject</label>
                <input class="field" id="subject" name="subject" value="{{ old('subject', $conversation?->subject) }}" placeholder="A short summary">
            </div>
            <div>
                <label class="label" for="body">Message</label>
                <textarea class="field" id="body" name="body" rows="6" required placeholder="Tell us what you need. Include the domain and the page if it is about a specific audit.">{{ old('body') }}</textarea>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Send the message</button>
        </form>

        <div class="rule my-5"></div>

        <h3 class="text-lg mb-2">Faster routes</h3>
        <ul class="space-y-2" style="font-size:.92rem;">
            <li class="flex gap-2">
                @include('partials.icons', ['name' => 'mail', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                <span><a href="{{ route('contact.create') }}" class="underline">Use the contact form</a> for anything that is not about your account.</span>
            </li>
            <li class="flex gap-2">
                @include('partials.icons', ['name' => 'document', 'class' => 'w-4 h-4 shrink-0 mt-1'])
                <span><a href="{{ route('blog.index') }}" class="underline">Read the blog</a> for approval guidance and policy explainers.</span>
            </li>
        </ul>
    </div>
</div>

@endsection
