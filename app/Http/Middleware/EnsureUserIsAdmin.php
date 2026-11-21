<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restrict the back office to explicitly allowlisted email addresses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userEmail = Str::lower((string) $request->user()?->email);
        $adminEmails = collect(config('admin.emails', []))
            ->map(fn (string $email) => Str::lower(trim($email)))
            ->filter()
            ->all();

        abort_unless($userEmail !== '' && in_array($userEmail, $adminEmails, true), 403);

        return $next($request);
    }
}
