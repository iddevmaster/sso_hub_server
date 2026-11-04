<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Integrations\Dtms\FeedClient::class, \App\Integrations\Dtms\DtmsClient::class);
        $this->app->bind(\App\Integrations\Dtms\ItemApplier::class, \App\Integrations\Dtms\SnapshotApplier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
