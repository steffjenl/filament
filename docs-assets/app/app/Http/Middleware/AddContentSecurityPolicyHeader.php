<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Support\Facades\FilamentView;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddContentSecurityPolicyHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);

        FilamentView::useCspNonce($nonce);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(
            'Content-Security-Policy',
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
        );

        return $response;
    }
}
