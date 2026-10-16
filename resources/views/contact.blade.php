
@extends('layouts.marketing')

@section('title', 'Contact | '.config('app.name'))
@section('description', 'Talk to the AdReady team about billing, audits, partnerships or anything else.')

@section('content')

<section class="band">
    <div class="wrap">
        <div class="grid grid-2" style="gap:3rem;align-items:start;">
            <div>
                <p class="eyebrow mb-3">Contact</p>
                <h1 class="mb-4">Tell us what you need</h1>
                <p class="lede mb-6">
                    Pick a reason so the right person picks it up. We answer every message within
                    one business day.
                </p>

                <div class="visual-frame">
                    @include('partials.visuals.contact-rail')
                </div>
            </div>

            <div class="card" style="padding:1.6rem;">
                <form method="POST" action="{{ route('contact.store') }}" class="form-grid">
                    @csrf

                    <div>
                        <label class="label" for="name">Your name</label>
                        <input class="field" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required>
                    </div>

                    <div>
                        <label class="label" for="email">Email</label>
                        <input class="field" id="email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required>
                    </div>

                    <div>
                        <label class="label" for="category">Reason</label>
                        <select class="field" id="category" name="category" required>
                            <option value="">Choose a reason</option>
                            @foreach ($categories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $preset) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="label" for="subject">Subject</label>
                        <input class="field" id="subject" name="subject" value="{{ old('subject') }}" required>
                    </div>

                    <div class="col-span-2">
                        <label class="label" for="message">Message</label>
                        <textarea class="field" id="message" name="message" rows="6" required placeholder="Include the domain and the page URL if it is about a specific site.">{{ old('message') }}</textarea>
                        <p class="hint mt-1">The more detail you give, the faster we can help.</p>
                    </div>

                    <div class="col-span-2">
                        <button class="btn btn-primary" type="submit">Send message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection
