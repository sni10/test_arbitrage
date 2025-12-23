<?php

namespace Tests\Feature\Connectors;

use App\Domain\Entities\Ticker;
use App\Infrastructure\Connectors\BybitConnector;
use App\Infrastructure\Connectors\PoloniexConnector;
use App\Infrastructure\Connectors\WhiteBitConnector;
use ccxt\Exchange;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CcxtConnectorSmokeTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function connectorProvider(): array
    {
        return [
            'bybit' => [BybitConnector::class, 'Bybit'],
            'poloniex' => [PoloniexConnector::class, 'Poloniex'],
            'whitebit' => [WhiteBitConnector::class, 'WhiteBIT'],
        ];
    }

    #[DataProvider('connectorProvider')]
    public function test_fetch_ticker_returns_valid_ticker(string $connectorClass, string $exchangeName): void
    {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/ccxt_ticker.json')),
            true
        );

        $mockExchange = $this->createMock(Exchange::class);
        $mockExchange->markets = ['BTC/USDT' => []];
        $mockExchange
            ->expects($this->once())
            ->method('fetch_ticker')
            ->with('BTC/USDT')
            ->willReturn($fixtureData);

        $connector = new $connectorClass($mockExchange);

        $ticker = $connector->fetchTicker('BTC/USDT');

        $this->assertInstanceOf(Ticker::class, $ticker);
        $this->assertSame('BTC/USDT', $ticker->symbol);
        $this->assertSame(36500.0, $ticker->price);
        $this->assertSame($exchangeName, $ticker->exchange);
        $this->assertSame(1700000000000, $ticker->timestamp);
    }

    #[DataProvider('connectorProvider')]
    public function test_get_available_pairs_returns_only_active_spot_pairs(
        string $connectorClass,
        string $exchangeName
    ): void {
        $fixtureData = json_decode(
            file_get_contents(base_path('tests/Fixtures/ccxt_markets.json')),
            true
        );

        $mockExchange = $this->createMock(Exchange::class);
        $mockExchange->markets = [];
        $mockExchange
            ->expects($this->once())
            ->method('load_markets')
            ->willReturnCallback(function () use ($mockExchange) {
                $mockExchange->markets = ['BTC/USDT' => [], 'ETH/USDT' => []];
            });

        $mockExchange
            ->expects($this->once())
            ->method('fetch_markets')
            ->willReturn($fixtureData);

        $connector = new $connectorClass($mockExchange);

        $pairs = $connector->getAvailablePairs();

        $this->assertIsArray($pairs);
        $this->assertCount(2, $pairs);
        $this->assertContains('BTC/USDT', $pairs);
        $this->assertContains('ETH/USDT', $pairs);
        $this->assertNotContains('SOL/USDT', $pairs);
        $this->assertSame($exchangeName, $connector->getName());
    }
}
