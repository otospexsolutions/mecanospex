<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Application\DTOs;

use App\Modules\Vehicle\Domain\Vehicle;
use Spatie\LaravelData\Data;

final class VehicleData extends Data
{
    public function __construct(
        public string $id,
        public string $tenant_id,
        public ?string $partner_id,
        public string $license_plate,
        public string $brand,
        public string $model,
        public ?int $year,
        public ?string $color,
        public ?int $mileage,
        public ?string $vin,
        public ?string $engine_code,
        public ?string $fuel_type,
        public ?string $transmission,
        public ?string $notes,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Vehicle $vehicle): self
    {
        return new self(
            id: $vehicle->id,
            tenant_id: $vehicle->tenant_id,
            partner_id: $vehicle->partner_id,
            license_plate: $vehicle->license_plate,
            brand: $vehicle->brand,
            model: $vehicle->model,
            year: $vehicle->year,
            color: $vehicle->color,
            mileage: $vehicle->mileage,
            vin: $vehicle->vin,
            engine_code: $vehicle->engine_code,
            fuel_type: $vehicle->fuel_type,
            transmission: $vehicle->transmission,
            notes: $vehicle->notes,
            created_at: $vehicle->created_at?->toIso8601String() ?? '',
            updated_at: $vehicle->updated_at?->toIso8601String() ?? '',
        );
    }
}
