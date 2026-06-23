<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P3: baseline HTTP security headers on every web response. CSP is intentionally
 * pragmatic (allows the inline Alpine/Vite bootstrap this app uses) while still
 * constraining script/style/connect origins to self. HSTS is only emitted over
 * HTTPS so it never traps local/dev HTTP.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'geolocation=(), microphone=(), camera=(), payment=()',
            'X-XSS-Protection'       => '0', // modern browsers: rely on CSP, disable legacy auditor
        ];

        // Content-Security-Policy: lock script/style/connect to self (+ the inline
        // bootstrap this app relies on) and allow images from self/data/https.
        //
        // Only enforced in production. In local/dev, assets + HMR are served from
        // the Vite dev server on a different origin (localhost:5173, ws://…), which
        // a 'self'-scoped CSP would block — so we skip CSP outside production to
        // avoid breaking the dev asset pipeline. (Prod serves built same-origin
        // assets, where the strict policy applies cleanly.)
        if (app()->environment('production')) {
            $headers['Content-Security-Policy'] = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "object-src 'none'",
            ]);
        }

        // HSTS only on secure connections (avoid pinning HTTP in dev).
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            // Don't clobber a header a downstream response set deliberately.
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
