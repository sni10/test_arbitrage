<?php

namespace App\Infrastructure\Connectors;

use App\Domain\Contracts\ExchangeConnectorInterface;
use App\Domain\Entities\Ticker;
use ccxt\Exchange;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for CCXT-based exchange connectors.
 *
 * Provides common functionality:
 * - CCXT exchange client management via dependency injection
 * - Symbol normalization to BASE/QUOTE format
 * - Error handling and retry logic
 * - Unified implementations of fetchTicker, fetchTickers, fetchMarkets, getAvailablePairs
 */
abstract class AbstractCcxtConnector implements ExchangeConnectorInterface
{
    protected Exchange $exchange;
    protected string $exchangeName;
    protected int $retryAttempts = 3;
    protected int $retryDelay;

    /**
     * Initialize the CCXT connector with injected exchange instance.
     *
     * @param Exchange $exchange CCXT exchange instance (injected via factory)
     * @param string $exchangeName Human-readable exchange name
     */
    public function __construct(Exchange $exchange, string $exchangeName)
    {
        $this->exchange = $exchange;
        $this->exchangeName = $exchangeName;
        $this->retryDelay = (int) config('RATE_LIMIT_DELAY', 200);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMarkets(): array
    {
        return $this->executeWithRetry(function () {
            $this->loadMarketsIfNeeded();
            return $this->exchange->fetch_markets();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePairs(): array
    {
        $markets = $this->fetchMarkets();
        $pairs = [];

        foreach ($markets as $market) {
            // Filter only active spot markets
            if (isset($market['active']) && $market['active'] &&
                isset($market['spot']) && $market['spot']) {
                $pairs[] = $market['symbol'];
            }
        }

        return $pairs;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchTicker(string $symbol): Ticker
    {
        return $this->executeWithRetry(function () use ($symbol) {
            $this->loadMarketsIfNeeded();
            $tickerData = $this->exchange->fetch_ticker($symbol);
            return $this->createTickerFromData($tickerData, $symbol);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function fetchTickers(): array
    {
        return $this->executeWithRetry(function () {
            $this->loadMarketsIfNeeded();
            $tickers = [];
            $tickersData = $this->exchange->fetch_tickers();

            foreach ($tickersData as $symbol => $tickerData) {
                try {
                    $tickers[] = $this->createTickerFromData($tickerData, $symbol);
                } catch (\Exception $e) {
                    // Skip tickers with missing data
                    continue;
                }
            }

            return $tickers;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->exchangeName;
    }

    /**
     * Load markets if not already loaded.
     */
    protected function loadMarketsIfNeeded(): void
    {
        if (empty($this->exchange->markets)) {
            $this->exchange->load_markets();
        }
    }

    /**
     * Normalize exchange-specific symbol to BASE/QUOTE format.
     *
     * @param string $symbol Symbol in any format
     * @return string Normalized symbol (e.g., 'BTC/USDT')
     */
    protected function normalizeSymbol(string $symbol): string
    {
        // CCXT already normalizes to BASE/QUOTE format
        // This method can be overridden for exchange-specific handling
        return $symbol;
    }

    /**
     * Execute a callable with retry logic.
     *
     * @param callable $callback Function to execute
     * @return mixed Result of the callback
     * @throws \Exception If all retry attempts fail
     */
    protected function executeWithRetry(callable $callback)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                return $callback();
            } catch (\ccxt\NetworkError $e) {
                $lastException = $e;
                if ($attempt < $this->retryAttempts) {
                    Log::warning("{$this->exchangeName} network error on attempt {$attempt}/{$this->retryAttempts}: {$e->getMessage()}");
                    usleep($this->retryDelay * 1000);
                } else {
                    Log::error("{$this->exchangeName} network error after {$this->retryAttempts} attempts: {$e->getMessage()}");
                }
            } catch (\ccxt\ExchangeError $e) {
                // Don't retry on exchange errors (invalid symbol, etc.)
                Log::error("{$this->exchangeName} API error: {$e->getMessage()}");
                throw new \Exception(
                    "{$this->exchangeName} API error: {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }

        throw new \Exception(
            "{$this->exchangeName} network error after {$this->retryAttempts} attempts: {$lastException->getMessage()}",
            0,
            $lastException
        );
    }

    /**
     * Create a Ticker entity from CCXT ticker data.
     *
     * @param array $tickerData CCXT ticker data
     * @param string $symbol Normalized symbol
     * @return Ticker
     */
    protected function createTickerFromData(array $tickerData, string $symbol): Ticker
    {
        $price = $tickerData['last'] ?? $tickerData['close'] ?? null;

        if ($price === null) {
            throw new \Exception(
                "{$this->exchangeName}: No price data available for {$symbol}"
            );
        }

        return new Ticker(
            symbol: $symbol,
            price: (float) $price,
            exchange: $this->exchangeName,
            timestamp: $tickerData['timestamp'] ?? time() * 1000
        );
    }
}
