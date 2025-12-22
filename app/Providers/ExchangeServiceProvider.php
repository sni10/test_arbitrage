<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ExchangeServiceProvider extends ServiceProvider
{
    /**
     * Register exchange connectors in the service container.
     *
     * This provider will register all exchange connectors as singletons
     * once the connector classes are implemented in the Infrastructure layer.
     */
    public function register(): void
    {
        // TODO: Register exchange connectors as singletons when implemented
        // Example:
        // $this->app->singleton('exchange.binance', BinanceConnector::class);
        // $this->app->singleton('exchange.jbex', JbexConnector::class);
        // $this->app->singleton('exchange.poloniex', PoloniexConnector::class);
        // $this->app->singleton('exchange.bybit', BybitConnector::class);
        // $this->app->singleton('exchange.whitebit', WhiteBitConnector::class);

        // TODO: Register array of all enabled connectors
        // $this->app->singleton('exchanges', function ($app) {
        //     $exchanges = [];
        //     $config = config('exchanges');
        //
        //     foreach ($config as $key => $settings) {
        //         if ($settings['enabled'] ?? false) {
        //             $exchanges[$key] = $app->make("exchange.{$key}");
        //         }
        //     }
        //
        //     return $exchanges;
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
