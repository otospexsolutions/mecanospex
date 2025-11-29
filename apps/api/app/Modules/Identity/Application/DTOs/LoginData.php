<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceName = null,
        public ?string $deviceId = null,
        public ?string $platform = null,
        public ?string $platformVersion = null,
        public ?string $appVersion = null,
    ) {}
}
