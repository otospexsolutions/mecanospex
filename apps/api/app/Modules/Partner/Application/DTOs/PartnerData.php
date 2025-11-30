<?php

declare(strict_types=1);

namespace App\Modules\Partner\Application\DTOs;

use App\Modules\Partner\Domain\Enums\PartnerType;
use App\Modules\Partner\Domain\Partner;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PartnerData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public PartnerType $type,
        public ?string $code,
        public ?string $email,
        public ?string $phone,
        public ?string $country_code,
        public ?string $vat_number,
        public ?string $notes,
        public string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Partner $partner): self
    {
        return new self(
            id: $partner->id,
            name: $partner->name,
            type: $partner->type,
            code: $partner->code,
            email: $partner->email,
            phone: $partner->phone,
            country_code: $partner->country_code,
            vat_number: $partner->vat_number,
            notes: $partner->notes,
            created_at: $partner->created_at?->toIso8601String() ?? '',
            updated_at: $partner->updated_at?->toIso8601String(),
        );
    }
}
