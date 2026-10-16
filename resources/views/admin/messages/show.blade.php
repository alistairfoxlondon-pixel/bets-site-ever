
@extends('layouts.admin')

@section('title', $message->subject)
@section('heading', $message->subject)
@section('subheading', $message->name.' <'.$message->email.'>')

@section('content')

<div class="grid grid-2" style="gap:1.25rem;align-items:start;">
    <div class="panel">
        <h2 class="text-lg mb-3">Message</h2>
        <p style="white-space:pre-line;font-size:.95rem;">{{ $message->message }}</p>

        <div class="rule my-4"></div>

        <dl class="space-y-2" style="font-size:.9rem;">
            <div class="flex justify-between"><dt class="muted">Reason</dt><dd>{{ ucfirst(str_replace('_', ' ', $message->category)) }}</dd></div>
            <div class="flex justify-between"><dt class="muted">Received</dt><dd>{{ $message->created_at->format('j M Y, H:i') }}</dd></div>
            <div class="flex justify-between"><dt class="muted">IP</dt><dd class="mono">{{ $message->ip ?? 'unknown' }}</dd></div>
            @if ($message->user)
                <div class="flex justify-between"><dt class="muted">Account</dt><dd>{{ $message->user->email }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="panel">
        <h2 class="text-lg mb-3">Reply</h2>
        <form method="POST" action="{{ route('admin.messages.reply', $message) }}" class="space-y-3">
            @csrf
            <textarea class="field" name="reply" rows="8" required placeholder="Write the answer here.">{{ old('reply', $message->reply) }}</textarea>
            <button class="btn btn-primary btn-block" type="submit">Save the reply</button>
        </form>

        <div class="rule my-4"></div>

        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}"
              onsubmit="return confirm('Delete this message?');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger btn-sm btn-block" type="submit">Delete the message</button>
        </form>
    </div>
</div>

@endsection
