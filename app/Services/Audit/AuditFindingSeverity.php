<?php

namespace App\Services\Audit;

final class AuditFindingSeverity
{
    public const CRITICAL = 'critical';

    public const WARNING = 'warning';

    public const PASS = 'pass';

    public const INFO = 'info';

    public static function all(): array
    {
        return [self::CRITICAL, self::WARNING, self::PASS, self::INFO];
    }
}
