<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum VerificationStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
}
