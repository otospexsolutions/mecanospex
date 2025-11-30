<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum DocumentReviewStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
}
