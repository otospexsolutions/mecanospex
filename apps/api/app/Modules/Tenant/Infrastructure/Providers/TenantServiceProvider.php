<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Infrastructure\Providers;

use App\Modules\Tenant\Application\Commands\CreateTenantCommand;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateTenantCommand::class,
            ]);
        }
    }
}
