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

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', $this->permissionsPolicy($request));

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Deny powerful device APIs by default. Camera is allowed only on the
     * ticket scanner page (same-origin), where QR scanning requires it.
     */
    private function permissionsPolicy(Request $request): string
    {
        $camera = $request->is('admin/ticket-scanner', 'admin/ticket-scanner/*')
            ? 'camera=(self)'
            : 'camera=()';

        return "{$camera}, microphone=(), geolocation=()";
    }
}
