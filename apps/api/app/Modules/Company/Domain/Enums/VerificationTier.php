<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum VerificationTier: string
{
    case Basic = 'basic';
    case Standard = 'standard';
    case Enhanced = 'enhanced';
    case Certified = 'certified';
}
