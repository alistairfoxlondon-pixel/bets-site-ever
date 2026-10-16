
@if (session('status'))
    <div class="flash flash-success">{{ session('status') }}</div>
@endif

@if (session('error'))
    <div class="flash flash-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="flash flash-error">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
