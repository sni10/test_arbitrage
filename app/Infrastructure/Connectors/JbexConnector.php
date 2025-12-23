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
 * - Base URL: https://api.jucoin.com
 * - Authentication: public endpoints require no auth
 * - Symbol format: btc_usdt (underscore, lowercase), normalized to BTC/USDT
 * - Endpoints: /v1/spot/public/symbol, /v1/spot/public/ticker/price
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
     * @param  HttpClientFactory  $httpFactory  HTTP client factory
     * @param  array  $config  Exchange configuration from config/exchanges.php
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
        $apiSymbol = $this->denormalizeSymbol($symbol);
        $url = $this->baseUrl.$this->endpoints['ticker_price'];

        $payload = $this->executeHttpRequest($url, ['symbol' => $apiSymbol]);
        $data = $payload['data'] ?? null;

        if (is_array($data) && array_key_exists('p', $data)) {
            $tickerData = $data;
        } else {
            $tickerData = is_array($data) ? ($data[0] ?? null) : null;
        }

        if (! is_array($tickerData) || ! isset($tickerData['p'])) {
            throw new \Exception("JBEX: No price data available for {$symbol}");
        }

        return new Ticker(
            symbol: $symbol,
            price: (float) $tickerData['p'],
            exchange: 'JBEX',
            timestamp: isset($tickerData['t']) ? (int) $tickerData['t'] : (int) (time() * 1000)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fetchTickers(): array
    {
        $url = $this->baseUrl.$this->endpoints['ticker_price'];
        $payload = $this->executeHttpRequest($url);
        $data = $payload['data'] ?? null;
        $tickers = [];

        if (! is_array($data)) {
            return $tickers;
        }

        foreach ($data as $tickerData) {
            if (! isset($tickerData['s']) || ! isset($tickerData['p'])) {
                continue;
            }

            $normalizedSymbol = $this->normalizeSymbol($tickerData['s']);

            $tickers[] = new Ticker(
                symbol: $normalizedSymbol,
                price: (float) $tickerData['p'],
                exchange: 'JBEX',
                timestamp: isset($tickerData['t']) ? (int) $tickerData['t'] : (int) (time() * 1000)
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

        $url = $this->baseUrl.$this->endpoints['broker_info'];
        $payload = $this->executeHttpRequest($url);
        $data = $payload['data'] ?? null;
        $markets = [];

        if (! is_array($data) || ! isset($data['symbols']) || ! is_array($data['symbols'])) {
            throw new \Exception('JBEX: Invalid symbol response format');
        }

        foreach ($data['symbols'] as $marketData) {
            if (! isset($marketData['symbol'])) {
                continue;
            }

            $symbol = $marketData['symbol'];
            $base = $marketData['baseCurrency'] ?? null;
            $quote = $marketData['quoteCurrency'] ?? null;
            $normalizedSymbol = $this->normalizeSymbol($symbol, $base, $quote);

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
                'active' => ($marketData['state'] ?? 'ONLINE') === 'ONLINE'
                    && ($marketData['tradingEnabled'] ?? true),
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
     * @param  string  $url  Full URL to request
     * @param  array  $params  Query parameters
     * @return array Response data as array
     *
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

        $payload = $response->json();

        if (is_array($payload) && isset($payload['code']) && (int) $payload['code'] !== 200) {
            $message = $payload['msg'] ?? 'Unknown error';
            Log::error("JBEX API error: code {$payload['code']} for URL: {$url} ({$message})");
            throw new \Exception("JBEX API error: {$message}");
        }

        return $payload;
    }

    /**
     * Normalize JBEX symbol to BASE/QUOTE format.
     *
     * Converts btc_usdt -> BTC/USDT
     *
     * @param  string  $symbol  JBEX symbol (e.g., 'btc_usdt')
     * @return string Normalized symbol (e.g., 'BTC/USDT')
     */
    private function normalizeSymbol(string $symbol, ?string $base = null, ?string $quote = null): string
    {
        if ($base !== null && $quote !== null && $base !== '' && $quote !== '') {
            return strtoupper($base).'/'.strtoupper($quote);
        }

        if (str_contains($symbol, '_')) {
            [$basePart, $quotePart] = explode('_', $symbol, 2);
            if ($basePart !== '' && $quotePart !== '') {
                return strtoupper($basePart).'/'.strtoupper($quotePart);
            }
        }

        if (str_contains($symbol, '/')) {
            return strtoupper($symbol);
        }

        // Common quote currencies to try
        $quotes = ['USDT', 'USDC', 'BTC', 'ETH', 'BNB', 'USD'];

        foreach ($quotes as $quote) {
            if (str_ends_with($symbol, $quote)) {
                $base = substr($symbol, 0, -strlen($quote));
                if (! empty($base)) {
                    return $base.'/'.$quote;
                }
            }
        }

        // Fallback: return as-is if no match
        return $symbol;
    }

    /**
     * Denormalize BASE/QUOTE symbol to JBEX format.
     *
     * Converts BTC/USDT -> btc_usdt
     *
     * @param  string  $symbol  Normalized symbol (e.g., 'BTC/USDT')
     * @return string JBEX symbol (e.g., 'btc_usdt')
     */
    private function denormalizeSymbol(string $symbol): string
    {
        return strtolower(str_replace('/', '_', $symbol));
    }
}
