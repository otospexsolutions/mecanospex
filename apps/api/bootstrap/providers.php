<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider::class,
    App\Modules\Tenant\Infrastructure\Providers\TenantServiceProvider::class,
];
