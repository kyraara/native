<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $scriptSources = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://cdn.jsdelivr.net'];
        $styleSources = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://cdnjs.cloudflare.com', 'https://cdn.jsdelivr.net'];
        $fontSources = ["'self'", 'data:', 'https://fonts.gstatic.com', 'https://cdnjs.cloudflare.com'];
        $imageSources = ["'self'", 'data:', 'https:'];
        $connectSources = ["'self'"];

        if (app()->isLocal()) {
            $viteHttpSources = [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
            ];

            $scriptSources = [...$scriptSources, ...$viteHttpSources];
            $styleSources = [...$styleSources, ...$viteHttpSources];
            $fontSources = [...$fontSources, ...$viteHttpSources];
            $imageSources = [...$imageSources, ...$viteHttpSources];
            $connectSources = [
                ...$connectSources,
                ...$viteHttpSources,
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
            ];
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        $contentSecurityPolicy = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            'script-src '.implode(' ', $scriptSources),
            'style-src '.implode(' ', $styleSources),
            'font-src '.implode(' ', $fontSources),
            'img-src '.implode(' ', $imageSources),
            'connect-src '.implode(' ', $connectSources),
        ];

        if (! app()->environment(['local', 'testing'])) {
            $contentSecurityPolicy[] = 'upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $contentSecurityPolicy));

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
