<?php

namespace App\Services;

use Illuminate\Http\Request;

class OrderTrackingAccess
{
    public function grant(Request $request, string $token): void
    {
        $grants = array_filter($request->session()->get('tracking_access', []), fn ($expiry) => is_numeric($expiry) && $expiry > now()->timestamp);
        $grants[$token] = now()->addMinutes(30)->timestamp;
        $request->session()->put('tracking_access', $grants);
    }

    public function allows(Request $request, string $token): bool
    {
        $expiry = $request->session()->get("tracking_access.{$token}", 0);

        return is_numeric($expiry) && $expiry > now()->timestamp;
    }
}
