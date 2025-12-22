<?php

namespace App\Providers;

use App\Application\UseCases\FindArbitrageUseCase;
use App\Application\UseCases\GetBestPriceUseCase;
use App\Infrastructure\Connectors\BinanceConnector;
use App\Infrastructure\Connectors\BybitConnector;
use App\Infrastructure\Connectors\JbexConnector;
use App\Infrastructure\Connectors\PoloniexConnector;
use App\Infrastructure\Connectors\WhiteBitConnector;
use App\Infrastructure\Factories\CcxtClientFactory;
use App\Infrastructure\Factories\HttpClientFactory;
use Illuminate\Support\ServiceProvider;

class ExchangeServiceProvider extends ServiceProvider
{
    /**
     * Register exchange connectors and factories in the service container.
     *
     * Registers:
     * - CcxtClientFactory for creating CCXT exchange instances
     * - HttpClientFactory for creating HTTP clients
     * - All exchange connectors with proper dependency injection
     * - Array of enabled connectors for use in application layer
     */
    public function register(): void
    {
        // Register factories as singletons
        $this->app->singleton(CcxtClientFactory::class);
        $this->app->singleton(HttpClientFactory::class);

        // Register CCXT-based connectors with factory injection
        $this->app->singleton('exchange.binance', function ($app) {
            $factory = $app->make(CcxtClientFactory::class);
            $exchange = $factory->createBinance();

            return new BinanceConnector($exchange);
        });

        $this->app->singleton('exchange.poloniex', function ($app) {
            $factory = $app->make(CcxtClientFactory::class);
            $exchange = $factory->createPoloniex();

            return new PoloniexConnector($exchange);
        });

        $this->app->singleton('exchange.bybit', function ($app) {
            $factory = $app->make(CcxtClientFactory::class);
            $exchange = $factory->createBybit();

            return new BybitConnector($exchange);
        });

        $this->app->singleton('exchange.whitebit', function ($app) {
            $factory = $app->make(CcxtClientFactory::class);
            $exchange = $factory->createWhitebit();

            return new WhiteBitConnector($exchange);
        });

        // Register JBEX connector with HTTP factory injection
        $this->app->singleton('exchange.jbex', function ($app) {
            $httpFactory = $app->make(HttpClientFactory::class);
            $config = config('exchanges.jbex');

            return new JbexConnector($httpFactory, $config);
        });

        // Register array of all enabled connectors
        $this->app->singleton('exchanges', function ($app) {
            $exchanges = [];
            $config = config('exchanges');

            foreach ($config as $key => $settings) {
                if ($settings['enabled'] ?? false) {
                    $exchanges[$key] = $app->make("exchange.{$key}");
                }
            }

            return $exchanges;
        });

        // Register use cases with explicit dependency injection
        $this->app->singleton(GetBestPriceUseCase::class, function ($app) {
            return new GetBestPriceUseCase(
                exchanges: array_values($app->make('exchanges')),
                priceAnalyzer: $app->make(\App\Domain\Services\PriceAnalyzer::class)
            );
        });

        $this->app->singleton(FindArbitrageUseCase::class, function ($app) {
            return new FindArbitrageUseCase(
                exchanges: array_values($app->make('exchanges')),
                commonPairsService: $app->make(\App\Application\Services\CommonPairsService::class),
                arbitrageCalculator: $app->make(\App\Domain\Services\ArbitrageCalculator::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
