<?php

namespace Tests\Feature\Connectors;

use App\Domain\Entities\Ticker;
use App\Infrastructure\Connectors\JbexConnector;
use App\Infrastructure\Factories\HttpClientFactory;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for JbexConnector.
 *
 * Tests integration with custom HTTP implementation using mocks and fixtures.
 * No real network calls are made.
 */
class JbexConnectorTest extends TestCase
{
    private string $baseUrl = 'https://api.jbex.com';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    /**
     * Test fetchTicker returns valid Ticker entity.
     */
    public function test_fetch_ticker_returns_valid_ticker(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/jbex_ticker_single.json')),
            true
        );

        Http::fake([
            $this->baseUrl . '/openapi/quote/v1/ticker/price*' => Http::response($fixtureData, 200),
        ]);

        $connector = $this->app->make('exchange.jbex');
        $ticker = $connector->fetchTicker('BTC/USDT');

        $this->assertInstanceOf(Ticker::class, $ticker);
        $this->assertSame('BTC/USDT', $ticker->symbol);
        $this->assertSame(42150.50, $ticker->price);
        $this->assertSame('JBEX', $ticker->exchange);
        $this->assertIsInt($ticker->timestamp);
    }

    /**
     * Test fetchTickers returns array of Ticker entities.
     */
    public function test_fetch_tickers_returns_array_of_tickers(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/jbex_tickers_all.json')),
            true
        );

        Http::fake([
            $this->baseUrl . '/openapi/quote/v1/ticker/price*' => Http::response($fixtureData, 200),
        ]);

        $connector = $this->app->make('exchange.jbex');
        $tickers = $connector->fetchTickers();

        $this->assertIsArray($tickers);
        $this->assertCount(3, $tickers);
        $this->assertContainsOnlyInstancesOf(Ticker::class, $tickers);
        $this->assertSame('BTC/USDT', $tickers[0]->symbol);
        $this->assertSame('ETH/USDT', $tickers[1]->symbol);
        $this->assertSame('SOL/USDT', $tickers[2]->symbol);
    }

    /**
     * Test fetchMarkets returns array of market structures.
     */
    public function test_fetch_markets_returns_array_of_markets(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/jbex_broker_info.json')),
            true
        );

        Http::fake([
            $this->baseUrl . '/openapi/v1/brokerInfo*' => Http::response($fixtureData, 200),
        ]);

        $connector = $this->app->make('exchange.jbex');
        $markets = $connector->fetchMarkets();

        $this->assertIsArray($markets);
        $this->assertCount(3, $markets);
        $this->assertSame('BTC/USDT', $markets[0]['symbol']);
        $this->assertSame('ETH/USDT', $markets[1]['symbol']);
        $this->assertSame('SOL/USDT', $markets[2]['symbol']);
        $this->assertTrue($markets[0]['active']);
        $this->assertTrue($markets[0]['spot']);
        $this->assertSame('BTC', $markets[0]['base']);
        $this->assertSame('USDT', $markets[0]['quote']);
    }

    /**
     * Test getAvailablePairs returns only active spot pairs.
     */
    public function test_get_available_pairs_returns_only_active_spot_pairs(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/jbex_broker_info.json')),
            true
        );

        Http::fake([
            $this->baseUrl . '/openapi/v1/brokerInfo*' => Http::response($fixtureData, 200),
        ]);

        $connector = $this->app->make('exchange.jbex');
        $pairs = $connector->getAvailablePairs();

        $this->assertIsArray($pairs);
        $this->assertCount(2, $pairs); // Only BTC/USDT and ETH/USDT (SOL/USDT is HALT)
        $this->assertContains('BTC/USDT', $pairs);
        $this->assertContains('ETH/USDT', $pairs);
        $this->assertNotContains('SOL/USDT', $pairs);
    }

    /**
     * Test getName returns correct exchange name.
     */
    public function test_get_name_returns_jbex(): void
    {
        $connector = $this->app->make('exchange.jbex');
        $this->assertSame('JBEX', $connector->getName());
    }

    /**
     * Test fetchTicker throws exception when price is missing.
     */
    public function test_fetch_ticker_throws_exception_when_price_missing(): void
    {
        Http::fake([
            $this->baseUrl . '/openapi/quote/v1/ticker/price*' => Http::response([
                'symbol' => 'BTCUSDT',
            ], 200),
        ]);

        $connector = $this->app->make('exchange.jbex');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No price data available');

        $connector->fetchTicker('BTC/USDT');
    }

    /**
     * Test fetchTicker throws exception on HTTP error.
     */
    public function test_fetch_ticker_throws_exception_on_http_error(): void
    {
        Http::fake([
            $this->baseUrl . '/openapi/quote/v1/ticker/price*' => Http::response([], 500),
        ]);

        $connector = $this->app->make('exchange.jbex');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/500/');

        $connector->fetchTicker('BTC/USDT');
    }

    /**
     * Test fetchMarkets throws exception on invalid response format.
     */
    public function test_fetch_markets_throws_exception_on_invalid_response(): void
    {
        Http::fake([
            $this->baseUrl . '/openapi/v1/brokerInfo*' => Http::response([
                'invalid' => 'data',
            ], 200),
        ]);

        $connector = $this->app->make('exchange.jbex');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid brokerInfo response format');

        $connector->fetchMarkets();
    }

    /**
     * Test connector can be resolved from Service Container.
     */
    public function test_connector_can_be_resolved_from_service_container(): void
    {
        $connector = $this->app->make('exchange.jbex');

        $this->assertInstanceOf(JbexConnector::class, $connector);
        $this->assertSame('JBEX', $connector->getName());
    }

    /**
     * Test connector is registered as singleton.
     */
    public function test_connector_is_singleton(): void
    {
        $connector1 = $this->app->make('exchange.jbex');
        $connector2 = $this->app->make('exchange.jbex');

        $this->assertSame($connector1, $connector2);
    }

    /**
     * Test HttpClientFactory is properly injected.
     */
    public function test_http_client_factory_is_injected(): void
    {
        $factory = $this->app->make(HttpClientFactory::class);

        $this->assertInstanceOf(HttpClientFactory::class, $factory);
    }

    /**
     * Test symbol normalization from JBEX format to BASE/QUOTE.
     */
    public function test_symbol_normalization_in_fetch_tickers(): void
    {
        $fixtureData = [
            ['symbol' => 'BTCUSDT', 'price' => '42150.50'],
            ['symbol' => 'ETHUSDT', 'price' => '2234.10'],
        ];

        Http::fake([
            $this->baseUrl . '/openapi/quote/v1/ticker/price*' => Http::response($fixtureData, 200),
        ]);

        $connector = $this->app->make('exchange.jbex');
        $tickers = $connector->fetchTickers();

        $this->assertSame('BTC/USDT', $tickers[0]->symbol);
        $this->assertSame('ETH/USDT', $tickers[1]->symbol);
    }

    /**
     * Test markets cache is used on subsequent calls.
     */
    public function test_markets_cache_is_used(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/jbex_broker_info.json')),
            true
        );

        Http::fake([
            $this->baseUrl . '/openapi/v1/brokerInfo*' => Http::response($fixtureData, 200),
        ]);

        $connector = $this->app->make('exchange.jbex');

        // First call - should hit API
        $markets1 = $connector->fetchMarkets();

        // Second call - should use cache (no additional HTTP request)
        $markets2 = $connector->fetchMarkets();

        $this->assertSame($markets1, $markets2);

        // Verify only one HTTP request was made
        Http::assertSentCount(1);
    }
}
