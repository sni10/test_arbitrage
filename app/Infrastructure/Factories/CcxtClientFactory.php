<?php

namespace App\Infrastructure\Factories;

use ccxt\Exchange;

/**
 * Factory for creating CCXT exchange instances.
 *
 * Centralizes CCXT client creation to eliminate code duplication
 * and follow Dependency Inversion Principle.
 */
class CcxtClientFactory
{
    private int $timeout;
    private bool $enableRateLimit;

    /**
     * Initialize factory with configuration.
     */
    public function __construct()
    {
        $this->timeout = (int) config('API_TIMEOUT', 5000);
        $this->enableRateLimit = true;
    }

    /**
     * Create a CCXT exchange instance by name.
     *
     * @param string $exchangeName Exchange class name (e.g., 'binance', 'bybit')
     * @param array $options Additional options to pass to exchange constructor
     * @return Exchange
     * @throws \Exception If exchange class doesn't exist
     */
    public function create(string $exchangeName, array $options = []): Exchange
    {
        $className = "\\ccxt\\{$exchangeName}";

        if (!class_exists($className)) {
            throw new \Exception("CCXT exchange class not found: {$className}");
        }

        $config = array_merge([
            'enableRateLimit' => $this->enableRateLimit,
            'timeout' => $this->timeout,
        ], $options);

        return new $className($config);
    }

    /**
     * Create Binance exchange instance.
     *
     * @return Exchange
     */
    public function createBinance(): Exchange
    {
        return $this->create('binance');
    }

    /**
     * Create Poloniex exchange instance.
     *
     * @return Exchange
     */
    public function createPoloniex(): Exchange
    {
        return $this->create('poloniex');
    }

    /**
     * Create Bybit exchange instance.
     *
     * @return Exchange
     */
    public function createBybit(): Exchange
    {
        return $this->create('bybit');
    }

    /**
     * Create WhiteBIT exchange instance.
     *
     * @return Exchange
     */
    public function createWhitebit(): Exchange
    {
        return $this->create('whitebit');
    }
}
