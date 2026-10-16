@php
    $manifestFile = public_path('build/manifest.json');
    $manifest = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) : [];
    $css = $manifest['resources/css/app.css']['file'] ?? null;
    $js = $manifest['resources/js/app.js']['file'] ?? null;
@endphp
@if ($css)
    <link rel="stylesheet" href="/build/{{ $css }}">
@endif
@if ($js)
    <script type="module" src="/build/{{ $js }}"></script>
@endif
