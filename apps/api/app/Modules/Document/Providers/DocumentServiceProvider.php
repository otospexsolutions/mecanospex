<?php

declare(strict_types=1);

namespace App\Modules\Document\Providers;

use App\Modules\Document\Domain\Services\DocumentNumberingService;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentNumberingService::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Presentation/routes.php');
    }
}
