<?php

namespace App\Http\Controllers;

use App\Services\Affiliate\AffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function capture(string $code, Request $request, AffiliateService $affiliate): RedirectResponse
    {
        $code = strtoupper($code);

        $affiliate->trackClick(
            $code,
            $request->ip(),
            $request->userAgent(),
            $request->path(),
            $request->header('referer'),
            $request->header('cf-ipcountry'),
        );

        return redirect()->route('register', ['ref' => $code]);
    }
}
