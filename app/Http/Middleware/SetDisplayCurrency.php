<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDisplayCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        $requested = strtoupper((string) $request->query('currency', ''));

        if (in_array($requested, ['USD', 'BDT'], true)) {
            $request->session()->put('display_currency', $requested);
            cookie()->queue(cookie('display_currency', $requested, 60 * 24 * 365));
        }

        $currency = $request->session()->get('display_currency')
            ?: $request->cookie('display_currency')
            ?: ($request->user()->currency ?? 'USD');

        if (! in_array($currency, ['USD', 'BDT'], true)) {
            $currency = 'USD';
        }

        $request->attributes->set('display_currency', $currency);
        view()->share('displayCurrency', $currency);

        return $next($request);
    }
}
