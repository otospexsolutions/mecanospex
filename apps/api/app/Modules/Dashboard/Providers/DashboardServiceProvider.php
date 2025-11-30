<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes.php');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
