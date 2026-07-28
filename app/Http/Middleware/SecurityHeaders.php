<?php

declare(strict_types=1);

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
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($response));

        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(Response $response): string
    {
        $scriptSources = [
            "'self'",
            "'unsafe-eval'",
            ...$this->inlineScriptHashes($response),
        ];

        $styleSources = ["'self'"];

        if ($this->containsInlineStyles($response)) {
            $styleSources[] = "'unsafe-inline'";
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            'style-src '.implode(' ', array_unique($styleSources)),
            'script-src '.implode(' ', array_unique($scriptSources)),
            "script-src-attr 'none'",
            "connect-src 'self'",
        ]);
    }

    /** @return list<string> */
    private function inlineScriptHashes(Response $response): array
    {
        $content = $this->htmlContent($response);

        if ($content === null) {
            return [];
        }

        preg_match_all(
            '/<script\b(?![^>]*\bsrc\s*=)[^>]*>(.*?)<\/script>/is',
            $content,
            $matches
        );

        $hashes = [];

        foreach ($matches[1] ?? [] as $script) {
            if (! is_string($script)) {
                continue;
            }

            $hashes[] = "'sha256-".base64_encode(hash('sha256', $script, true))."'";
        }

        return array_values(array_unique($hashes));
    }

    private function containsInlineStyles(Response $response): bool
    {
        $content = $this->htmlContent($response);

        if ($content === null) {
            return false;
        }

        return preg_match('/<style\b[^>]*>/i', $content) === 1
            || preg_match('/\sstyle\s*=\s*(?:["\']|[^\s>]+)/i', $content) === 1;
    }

    private function htmlContent(Response $response): ?string
    {
        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        if (
            $contentType !== ''
            && ! str_starts_with($contentType, 'text/html')
            && ! str_starts_with($contentType, 'application/xhtml+xml')
        ) {
            return null;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return null;
        }

        return $content;
    }
}
