<?php

namespace App\Enums;

/**
 * Identifies the entry point that originated an audit-logged action.
 */
enum AuditSource: string
{
    case Web = 'web';
    case Api = 'api';
    case Console = 'console';
    case System = 'system';

    /** Human-readable label for display. */
    public function label(): string
    {
        return match ($this) {
            self::Web => 'Web App',
            self::Api => 'API',
            self::Console => 'Console',
            self::System => 'System',
        };
    }
}
