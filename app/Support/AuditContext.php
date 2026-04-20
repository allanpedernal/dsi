<?php

namespace App\Support;

use App\Enums\AuditSource;
use Illuminate\Support\Str;

/**
 * Request-scoped singleton populated by TrackAuditSource middleware
 * and read by the LogsAuditActivity trait when writing activity rows.
 */
class AuditContext
{
    private AuditSource $source = AuditSource::System;

    private string $requestId;

    private ?string $ipAddress = null;

    private ?string $userAgent = null;

    public function __construct()
    {
        $this->requestId = (string) Str::uuid();
    }

    /** Stamp the current request's audit source and network fingerprint. */
    public function set(AuditSource $source, ?string $ip = null, ?string $ua = null): void
    {
        $this->source = $source;
        $this->ipAddress = $ip;
        $this->userAgent = $ua;
    }

    /** @return array{source: string, request_id: string, ip_address: ?string, user_agent: ?string} */
    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'request_id' => $this->requestId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }

    /** Current audit source (defaults to System until middleware sets it). */
    public function source(): AuditSource
    {
        return $this->source;
    }
}
