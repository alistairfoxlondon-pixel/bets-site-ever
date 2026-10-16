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
        @if ($checkoutModal)
            <h1 class="text-xl mb-2">Complete payment in secure checkout</h1>
            <p class="muted text-sm mb-5">Razorpay checkout should open automatically. If it does not, use the button below.</p>
            <button class="btn btn-primary btn-block" type="button" id="continue">Open secure checkout</button>
            <a href="{{ $fallbackUrl }}" class="btn btn-ghost btn-block mt-3">Return to invoice</a>
        @else
            <h1 class="text-xl mb-2">Taking you to the payment page</h1>
            <p class="muted text-sm mb-5">If nothing happens, use the button below.</p>
            <a href="{{ $url }}" class="btn btn-primary btn-block" id="continue">Continue to payment</a>
        @endif
    </div>
</div>

{!! $script !!}

@if ($checkoutModal)
    <script>
        document.getElementById('continue').addEventListener('click', function () {
            if (typeof window.adreadyOpenCheckout === 'function') {
                window.adreadyOpenCheckout();
            }
        });
    </script>
@else
    <script>
        document.getElementById('continue').addEventListener('click', function () {
            setTimeout(function () { window.location.href = @json($url); }, 50);
        });
        setTimeout(function () { window.location.href = @json($url); }, 1200);
    </script>
@endif
</body>
</html>
