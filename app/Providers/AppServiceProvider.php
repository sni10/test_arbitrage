<?php

namespace App\Providers;

use App\Application\Services\CommonPairsService;
use App\Application\UseCases\FindArbitrageUseCase;
use App\Application\UseCases\GetBestPriceUseCase;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(GetBestPriceUseCase::class)
            ->needs('$exchanges')
            ->give(fn ($app) => $app->make('exchanges'));

        $this->app->when(FindArbitrageUseCase::class)
            ->needs('$exchanges')
            ->give(fn ($app) => $app->make('exchanges'));

        $this->app->when(CommonPairsService::class)
            ->needs('$exchanges')
            ->give(fn ($app) => $app->make('exchanges'));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
