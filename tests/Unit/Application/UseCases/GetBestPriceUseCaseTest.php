<?php

namespace Tests\Unit\Application\UseCases;

use App\Application\UseCases\GetBestPriceUseCase;
use App\Domain\Contracts\ExchangeConnectorInterface;
use App\Domain\Entities\Ticker;
use App\Domain\Services\PriceAnalyzer;
use PHPUnit\Framework\TestCase;

class GetBestPriceUseCaseTest extends TestCase
{
    private PriceAnalyzer $priceAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceAnalyzer = new PriceAnalyzer();
    }

    public function test_returns_best_prices_for_pair(): void
    {
        $exchange1 = $this->createExchangeWithTicker('Binance', 'BTC/USDT', 42150.50);
        $exchange2 = $this->createExchangeWithTicker('JBEX', 'BTC/USDT', 42200.00);
        $exchange3 = $this->createExchangeWithTicker('Bybit', 'BTC/USDT', 42380.20);

        $exchanges = [$exchange1, $exchange2, $exchange3];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $result = $useCase->execute('BTC/USDT');

        $this->assertEquals('BTC/USDT', $result['pair']);
        $this->assertEquals('Binance', $result['min']['exchange']);
        $this->assertEquals(42150.50, $result['min']['price']);
        $this->assertEquals('Bybit', $result['max']['exchange']);
        $this->assertEquals(42380.20, $result['max']['price']);
        $this->assertEqualsWithDelta(229.70, $result['difference']['absolute'], 0.01);
        $this->assertEqualsWithDelta(0.545, $result['difference']['percent'], 0.001);
        $this->assertEquals(3, $result['exchanges_checked']);
        $this->assertEmpty($result['exchanges_failed']);
    }

    public function test_calculates_difference_correctly(): void
    {
        $exchange1 = $this->createExchangeWithTicker('Binance', 'ETH/USDT', 2234.10);
        $exchange2 = $this->createExchangeWithTicker('Poloniex', 'ETH/USDT', 2245.80);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $result = $useCase->execute('ETH/USDT');

        $this->assertEqualsWithDelta(11.70, $result['difference']['absolute'], 0.01);
        $this->assertEqualsWithDelta(0.524, $result['difference']['percent'], 0.001);
    }

    public function test_continues_when_one_exchange_fails(): void
    {
        $exchange1 = $this->createExchangeWithTicker('Binance', 'BTC/USDT', 42150.50);
        $exchange2 = $this->createExchangeFailure('JBEX', 'Connection timeout');
        $exchange3 = $this->createExchangeWithTicker('Bybit', 'BTC/USDT', 42380.20);

        $exchanges = [$exchange1, $exchange2, $exchange3];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $result = $useCase->execute('BTC/USDT');

        $this->assertEquals('BTC/USDT', $result['pair']);
        $this->assertEquals(2, $result['exchanges_checked']);
        $this->assertCount(1, $result['exchanges_failed']);
        $this->assertContains('JBEX', $result['exchanges_failed']);
    }

    public function test_continues_when_multiple_exchanges_fail(): void
    {
        $exchange1 = $this->createExchangeFailure('Binance', 'API error');
        $exchange2 = $this->createExchangeWithTicker('JBEX', 'BTC/USDT', 42200.00);
        $exchange3 = $this->createExchangeFailure('Bybit', 'Rate limit');

        $exchanges = [$exchange1, $exchange2, $exchange3];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $result = $useCase->execute('BTC/USDT');

        $this->assertEquals('BTC/USDT', $result['pair']);
        $this->assertEquals(1, $result['exchanges_checked']);
        $this->assertCount(2, $result['exchanges_failed']);
        $this->assertContains('Binance', $result['exchanges_failed']);
        $this->assertContains('Bybit', $result['exchanges_failed']);
    }

    public function test_throws_exception_when_all_exchanges_fail(): void
    {
        $exchange1 = $this->createExchangeFailure('Binance', 'Connection timeout');
        $exchange2 = $this->createExchangeFailure('JBEX', 'API error');

        $exchanges = [$exchange1, $exchange2];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Trading pair 'BTC/USDT' not found on any exchange");

        $useCase->execute('BTC/USDT');
    }

    public function test_throws_exception_when_pair_not_found(): void
    {
        $exchange1 = $this->createExchangeFailure('Binance', 'Symbol not found');
        $exchange2 = $this->createExchangeFailure('JBEX', 'Invalid symbol');

        $exchanges = [$exchange1, $exchange2];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Trading pair 'INVALID/PAIR' not found on any exchange");

        $useCase->execute('INVALID/PAIR');
    }

    public function test_throws_exception_when_no_exchanges_configured(): void
    {
        $exchanges = [];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No exchanges configured');

        $useCase->execute('BTC/USDT');
    }

    public function test_handles_same_price_on_all_exchanges(): void
    {
        $exchange1 = $this->createExchangeWithTicker('Binance', 'BTC/USDT', 42000.00);
        $exchange2 = $this->createExchangeWithTicker('JBEX', 'BTC/USDT', 42000.00);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $result = $useCase->execute('BTC/USDT');

        $this->assertEquals(42000.00, $result['min']['price']);
        $this->assertEquals(42000.00, $result['max']['price']);
        $this->assertEquals(0.0, $result['difference']['absolute']);
        $this->assertEquals(0.0, $result['difference']['percent']);
    }

    public function test_includes_timestamp_in_result(): void
    {
        $timestamp = time() * 1000;
        $exchange1 = $this->createExchangeWithTicker('Binance', 'BTC/USDT', 42150.50, $timestamp);

        $exchanges = [$exchange1];
        $useCase = new GetBestPriceUseCase($exchanges, $this->priceAnalyzer);

        $result = $useCase->execute('BTC/USDT');

        $this->assertEquals($timestamp, $result['min']['timestamp']);
        $this->assertEquals($timestamp, $result['max']['timestamp']);
    }

    private function createExchangeWithTicker(
        string $name,
        string $symbol,
        float $price,
        ?int $timestamp = null
    ): ExchangeConnectorInterface {
        $timestamp = $timestamp ?? time() * 1000;
        $ticker = new Ticker($symbol, $price, $name, $timestamp);

        $mock = $this->createMock(ExchangeConnectorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('fetchTicker')->with($symbol)->willReturn($ticker);

        return $mock;
    }

    private function createExchangeFailure(string $name, string $errorMessage): ExchangeConnectorInterface
    {
        $mock = $this->createMock(ExchangeConnectorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('fetchTicker')->willThrowException(new \Exception($errorMessage));

        return $mock;
    }
}
