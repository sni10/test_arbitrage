<?php

namespace App\Infrastructure\Connectors;

use App\Domain\Contracts\ExchangeConnectorInterface;
use App\Domain\Entities\Ticker;
use App\Infrastructure\Factories\HttpClientFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * JBEX exchange connector using custom HTTP implementation.
 *
 * JBEX is not supported by CCXT, so this connector uses direct REST API calls
 * via injected HttpClientFactory.
 *
 * API specifics:
 * - Base URL: https://api.jbex.com
 * - Authentication: X-BH-APIKEY header
 * - Symbol format: BTCUSDT (no separator), normalized to BTC/USDT
 * - Endpoints: /openapi/v1/brokerInfo, /openapi/quote/v1/ticker/price
 */
class JbexConnector implements ExchangeConnectorInterface
{
    private string $baseUrl;
    private array $endpoints;
    private ?array $marketsCache = null;
    private HttpClientFactory $httpFactory;

    /**
     * Initialize JBEX connector with injected HTTP client factory.
     *
     * @param HttpClientFactory $httpFactory HTTP client factory
     * @param array $config Exchange configuration from config/exchanges.php
     */
    public function __construct(HttpClientFactory $httpFactory, array $config)
    {
        $this->httpFactory = $httpFactory;
        $this->baseUrl = $config['api_url'];
        $this->endpoints = $config['endpoints'];
    }

    /**
     * {@inheritdoc}
     */
    public function fetchTicker(string $symbol): Ticker
    {
        $jbexSymbol = $this->denormalizeSymbol($symbol);
        $url = $this->baseUrl . $this->endpoints['ticker_price'];

        $data = $this->executeHttpRequest($url, ['symbol' => $jbexSymbol]);

        // JBEX ticker/price returns: {"symbol": "BTCUSDT", "price": "42150.50"}
        if (!isset($data['price'])) {
            throw new \Exception("JBEX: No price data available for {$symbol}");
        }

        return new Ticker(
            symbol: $symbol,
            price: (float) $data['price'],
            exchange: 'JBEX',
            timestamp: time() * 1000
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetchTickers(): array
    {
        $url = $this->baseUrl . $this->endpoints['ticker_price'];
        $data = $this->executeHttpRequest($url);
        $tickers = [];

        // JBEX returns array of tickers when no symbol specified
        foreach ($data as $tickerData) {
            if (!isset($tickerData['symbol']) || !isset($tickerData['price'])) {
                continue;
            }

            $normalizedSymbol = $this->normalizeSymbol($tickerData['symbol']);

            $tickers[] = new Ticker(
                symbol: $normalizedSymbol,
                price: (float) $tickerData['price'],
                exchange: 'JBEX',
                timestamp: time() * 1000
            );
        }

        return $tickers;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMarkets(): array
    {
        if ($this->marketsCache !== null) {
            return $this->marketsCache;
        }

        $url = $this->baseUrl . $this->endpoints['broker_info'];
        $data = $this->executeHttpRequest($url);
        $markets = [];

        // JBEX brokerInfo returns: {"symbols": [...]}
        if (!isset($data['symbols']) || !is_array($data['symbols'])) {
            throw new \Exception("JBEX: Invalid brokerInfo response format");
        }

        foreach ($data['symbols'] as $marketData) {
            if (!isset($marketData['symbol'])) {
                continue;
            }

            $symbol = $marketData['symbol'];
            $normalizedSymbol = $this->normalizeSymbol($symbol);

            // Parse BASE and QUOTE from normalized symbol
            $parts = explode('/', $normalizedSymbol);
            if (count($parts) !== 2) {
                continue;
            }

            $markets[] = [
                'id' => $symbol,
                'symbol' => $normalizedSymbol,
                'base' => $parts[0],
                'quote' => $parts[1],
                'baseId' => $parts[0],
                'quoteId' => $parts[1],
                'active' => ($marketData['status'] ?? 'TRADING') === 'TRADING',
                'spot' => true,
                'type' => 'spot',
                'info' => $marketData,
            ];
        }

        $this->marketsCache = $markets;
        return $markets;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailablePairs(): array
    {
        $markets = $this->fetchMarkets();
        $pairs = [];

        foreach ($markets as $market) {
            if ($market['active'] && $market['spot']) {
                $pairs[] = $market['symbol'];
            }
        }

        return $pairs;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'JBEX';
    }

    /**
     * Execute HTTP GET request to JBEX API.
     *
     * Centralizes HTTP logic with retry, timeout, and error handling.
     *
     * @param string $url Full URL to request
     * @param array $params Query parameters
     * @return array Response data as array
     * @throws \Exception If request fails
     */
    private function executeHttpRequest(string $url, array $params = []): array
    {
        $client = $this->httpFactory->createForJbex(config('exchanges.jbex.api_key', ''));
        $response = $client->get($url, $params);

        if ($response->failed()) {
            Log::error("JBEX API error: HTTP {$response->status()} for URL: {$url}");
            throw new \Exception("JBEX API error: HTTP {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Normalize JBEX symbol to BASE/QUOTE format.
     *
     * Converts BTCUSDT -> BTC/USDT
     *
     * @param string $symbol JBEX symbol (e.g., 'BTCUSDT')
     * @return string Normalized symbol (e.g., 'BTC/USDT')
     */
    private function normalizeSymbol(string $symbol): string
    {
        // Common quote currencies to try
        $quotes = ['USDT', 'USDC', 'BTC', 'ETH', 'BNB', 'USD'];

        foreach ($quotes as $quote) {
            if (str_ends_with($symbol, $quote)) {
                $base = substr($symbol, 0, -strlen($quote));
                if (!empty($base)) {
                    return $base . '/' . $quote;
                }
            }
        }

        // Fallback: return as-is if no match
        return $symbol;
    }

    /**
     * Denormalize BASE/QUOTE symbol to JBEX format.
     *
     * Converts BTC/USDT -> BTCUSDT
     *
     * @param string $symbol Normalized symbol (e.g., 'BTC/USDT')
     * @return string JBEX symbol (e.g., 'BTCUSDT')
     */
    private function denormalizeSymbol(string $symbol): string
    {
        return str_replace('/', '', $symbol);
    }
}
