
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting to payment</title>
    <link rel="icon" href="/brand/adready.svg" type="image/svg+xml">
    @include('partials.assets')
</head>
<body class="grid-paper">
<div class="min-h-screen flex items-center justify-center px-5">
    <div class="card" style="padding:2rem;max-width:420px;text-align:center;">
        <h1 class="text-xl mb-2">Taking you to the payment page</h1>
        <p class="muted text-sm mb-5">If nothing happens, use the button below.</p>
        <a href="{{ $url }}" class="btn btn-primary btn-block" id="continue">Continue to payment</a>
    </div>
</div>

{!! $script !!}

<script>
    document.getElementById('continue').addEventListener('click', function () {
        setTimeout(function () { window.location.href = @json($url); }, 50);
    });
    setTimeout(function () { window.location.href = @json($url); }, 1200);
</script>
</body>
</html>
