<?php

namespace App\Http\Middleware;

use App\Enums\AuditSource;
use App\Support\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stamps the request's audit source (web/api/console) and network fingerprint onto AuditContext.
 */
class TrackAuditSource
{
    public function __construct(private AuditContext $context) {}

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next, string $source = 'web'): Response
    {
        $this->context->set(
            AuditSource::from($source),
            $request->ip(),
            substr((string) $request->userAgent(), 0, 255),
        );

        return $next($request);
    }
}
