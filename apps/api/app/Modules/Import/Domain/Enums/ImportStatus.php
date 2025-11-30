<?php

declare(strict_types=1);

namespace App\Modules\Import\Domain\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Validating = 'validating';
    case Validated = 'validated';
    case Importing = 'importing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function canStartImport(): bool
    {
        return $this === self::Validated;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }

    public function isProcessing(): bool
    {
        return in_array($this, [self::Validating, self::Importing], true);
    }
}
