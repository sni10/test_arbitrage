<?php

namespace App\Domain\Contracts;

use App\Domain\Entities\Ticker;

/**
 * Unified interface for exchange connectors.
 *
 * Methods semantically match CCXT library conventions to ensure
 * compatibility between CCXT-based connectors and custom implementations (e.g., JBEX).
 */
interface ExchangeConnectorInterface
{
    /**
     * Fetch ticker data for a specific trading pair.
     *
     * @param  string  $symbol  Trading pair in BASE/QUOTE format (e.g., 'BTC/USDT')
     * @return Ticker Ticker entity with price and metadata
     *
     * @throws \Exception If the symbol is not available or API request fails
     */
    public function fetchTicker(string $symbol): Ticker;

    /**
     * Fetch ticker data for all available trading pairs.
     *
     * @return array<Ticker> Array of Ticker entities
     *
     * @throws \Exception If API request fails
     */
    public function fetchTickers(): array;

    /**
     * Fetch all available markets/trading pairs from the exchange.
     *
     * Returns raw market data including symbol, base, quote, limits, precision, etc.
     * Format should be normalized to match CCXT unified structure.
     *
     * @return array<array> Array of market structures
     *
     * @throws \Exception If API request fails
     */
    public function fetchMarkets(): array;

    /**
     * Get list of available trading pairs in BASE/QUOTE format.
     *
     * This is a convenience method that extracts normalized symbols
     * from markets data.
     *
     * @return array<string> Array of trading pair symbols (e.g., ['BTC/USDT', 'ETH/USDT'])
     *
     * @throws \Exception If API request fails
     */
    public function getAvailablePairs(): array;

    /**
     * Get the exchange name.
     *
     * @return string Exchange name (e.g., 'Binance', 'JBEX', 'Bybit')
     */
    public function getName(): string;
}
