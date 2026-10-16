@php
    $value = max(0, min(100, (int) ($value ?? 0)));
    $cells = (int) ($cells ?? 20);
    $filled = (int) round($cells * $value / 100);
    $class = $class ?? 'gauge';
@endphp
<table class="{{ $class }}" cellspacing="0" cellpadding="0">
    <tr>
        @for ($i = 0; $i < $cells; $i++)
            <td style="background-color: {{ $i < $filled ? '#12395a' : '#e2e0da' }};">&nbsp;</td>
        @endfor
    </tr>
</table>
