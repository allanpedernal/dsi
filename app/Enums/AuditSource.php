<?php

namespace App\Enums;

enum AuditSource: string
{
    case Web = 'web';
    case Api = 'api';
    case Console = 'console';
    case System = 'system';

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
