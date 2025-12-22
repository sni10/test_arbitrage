<?php

namespace Tests\Feature\Connectors;

use App\Domain\Entities\Ticker;
use App\Infrastructure\Connectors\BinanceConnector;
use App\Infrastructure\Factories\CcxtClientFactory;
use ccxt\Exchange;
use Tests\TestCase;

/**
 * Feature tests for BinanceConnector.
 *
 * Tests integration with CCXT library using mocks and fixtures.
 * No real network calls are made.
 */
class BinanceConnectorTest extends TestCase
{
    private BinanceConnector $connector;

    private Exchange $mockExchange;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock CCXT exchange
        $this->mockExchange = $this->createMock(Exchange::class);
        $this->mockExchange->markets = [];

        // Create connector with mocked exchange
        $this->connector = new BinanceConnector($this->mockExchange);
    }

    /**
     * Test fetchTicker returns valid Ticker entity.
     */
    public function test_fetch_ticker_returns_valid_ticker(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/ccxt_ticker.json')),
            true
        );

        $this->mockExchange->markets = ['BTC/USDT' => []];
        $this->mockExchange
            ->expects($this->once())
            ->method('fetch_ticker')
            ->with('BTC/USDT')
            ->willReturn($fixtureData);

        $ticker = $this->connector->fetchTicker('BTC/USDT');

        $this->assertInstanceOf(Ticker::class, $ticker);
        $this->assertSame('BTC/USDT', $ticker->symbol);
        $this->assertSame(36500.0, $ticker->price);
        $this->assertSame('Binance', $ticker->exchange);
        $this->assertSame(1700000000000, $ticker->timestamp);
    }

    /**
     * Test fetchTickers returns array of Ticker entities.
     */
    public function test_fetch_tickers_returns_array_of_tickers(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/ccxt_ticker.json')),
            true
        );

        $tickersData = [
            'BTC/USDT' => $fixtureData,
            'ETH/USDT' => array_merge($fixtureData, [
                'symbol' => 'ETH/USDT',
                'last' => 2234.10,
            ]),
        ];

        $this->mockExchange->markets = ['BTC/USDT' => [], 'ETH/USDT' => []];
        $this->mockExchange
            ->expects($this->once())
            ->method('fetch_tickers')
            ->willReturn($tickersData);

        $tickers = $this->connector->fetchTickers();

        $this->assertIsArray($tickers);
        $this->assertCount(2, $tickers);
        $this->assertContainsOnlyInstancesOf(Ticker::class, $tickers);
        $this->assertSame('BTC/USDT', $tickers[0]->symbol);
        $this->assertSame('ETH/USDT', $tickers[1]->symbol);
    }

    /**
     * Test fetchMarkets returns array of market structures.
     */
    public function test_fetch_markets_returns_array_of_markets(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/ccxt_markets.json')),
            true
        );

        $this->mockExchange->markets = [];
        $this->mockExchange
            ->expects($this->once())
            ->method('load_markets')
            ->willReturnCallback(function () {
                $this->mockExchange->markets = ['BTC/USDT' => [], 'ETH/USDT' => []];
            });

        $this->mockExchange
            ->expects($this->once())
            ->method('fetch_markets')
            ->willReturn($fixtureData);

        $markets = $this->connector->fetchMarkets();

        $this->assertIsArray($markets);
        $this->assertCount(3, $markets);
        $this->assertSame('BTC/USDT', $markets[0]['symbol']);
        $this->assertSame('ETH/USDT', $markets[1]['symbol']);
        $this->assertTrue($markets[0]['active']);
        $this->assertTrue($markets[0]['spot']);
    }

    /**
     * Test getAvailablePairs returns only active spot pairs.
     */
    public function test_get_available_pairs_returns_only_active_spot_pairs(): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/ccxt_markets.json')),
            true
        );

        $this->mockExchange->markets = [];
        $this->mockExchange
            ->expects($this->once())
            ->method('load_markets')
            ->willReturnCallback(function () {
                $this->mockExchange->markets = ['BTC/USDT' => [], 'ETH/USDT' => []];
            });

        $this->mockExchange
            ->expects($this->once())
            ->method('fetch_markets')
            ->willReturn($fixtureData);

        $pairs = $this->connector->getAvailablePairs();

        $this->assertIsArray($pairs);
        $this->assertCount(2, $pairs); // Only BTC/USDT and ETH/USDT (SOL/USDT is inactive)
        $this->assertContains('BTC/USDT', $pairs);
        $this->assertContains('ETH/USDT', $pairs);
        $this->assertNotContains('SOL/USDT', $pairs);
    }

    /**
     * Test getName returns correct exchange name.
     */
    public function test_get_name_returns_binance(): void
    {
        $this->assertSame('Binance', $this->connector->getName());
    }

    /**
     * Test fetchTicker throws exception when price is missing.
     */
    public function test_fetch_ticker_throws_exception_when_price_missing(): void
    {
        $this->mockExchange->markets = ['BTC/USDT' => []];
        $this->mockExchange
            ->expects($this->once())
            ->method('fetch_ticker')
            ->with('BTC/USDT')
            ->willReturn(['symbol' => 'BTC/USDT']); // No price data

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No price data available');

        $this->connector->fetchTicker('BTC/USDT');
    }

    /**
     * Test connector can be resolved from Service Container.
     */
    public function test_connector_can_be_resolved_from_service_container(): void
    {
        $connector = $this->app->make('exchange.binance');

        $this->assertInstanceOf(BinanceConnector::class, $connector);
        $this->assertSame('Binance', $connector->getName());
    }

    /**
     * Test connector is registered as singleton.
     */
    public function test_connector_is_singleton(): void
    {
        $connector1 = $this->app->make('exchange.binance');
        $connector2 = $this->app->make('exchange.binance');

        $this->assertSame($connector1, $connector2);
    }

    /**
     * Test CcxtClientFactory is properly injected.
     */
    public function test_ccxt_client_factory_is_injected(): void
    {
        $factory = $this->app->make(CcxtClientFactory::class);

        $this->assertInstanceOf(CcxtClientFactory::class, $factory);
    }
}
