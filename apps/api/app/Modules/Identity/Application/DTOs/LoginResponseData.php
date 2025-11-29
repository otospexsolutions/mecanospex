<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTOs;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final readonly class LoginResponseData
{
    public function __construct(
        public AuthUserData $user,
        public string $token,
        public string $tokenType,
        public ?string $deviceId,
    ) {}
}
