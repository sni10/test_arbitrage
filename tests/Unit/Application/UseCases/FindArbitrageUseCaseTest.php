<?php

namespace Tests\Unit\Application\UseCases;

use App\Application\Services\CommonPairsService;
use App\Application\UseCases\FindArbitrageUseCase;
use App\Domain\Contracts\ExchangeConnectorInterface;
use App\Domain\Entities\Ticker;
use App\Domain\Services\ArbitrageCalculator;
use PHPUnit\Framework\TestCase;

class FindArbitrageUseCaseTest extends TestCase
{
    private CommonPairsService $commonPairsService;
    private ArbitrageCalculator $arbitrageCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arbitrageCalculator = new ArbitrageCalculator();
    }

    public function test_finds_arbitrage_opportunities(): void
    {
        $commonPairs = ['BTC/USDT', 'ETH/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42150.50,
            'ETH/USDT' => 2234.10,
        ]);
        $exchange2 = $this->createExchangeWithMultipleTickers('Bybit', [
            'BTC/USDT' => 42380.20,
            'ETH/USDT' => 2245.80,
        ]);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('opportunities', $result);
        $this->assertArrayHasKey('total_found', $result);
        $this->assertArrayHasKey('pairs_checked', $result);
        $this->assertEquals(2, $result['pairs_checked']);
        $this->assertEquals(2, $result['total_found']);
        $this->assertCount(2, $result['opportunities']);
    }

    public function test_applies_min_profit_filter(): void
    {
        $commonPairs = ['BTC/USDT', 'ETH/USDT', 'SOL/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42150.50,  // 0.545% profit
            'ETH/USDT' => 2234.10,   // 0.524% profit
            'SOL/USDT' => 98.45,     // 0.477% profit
        ]);
        $exchange2 = $this->createExchangeWithMultipleTickers('Bybit', [
            'BTC/USDT' => 42380.20,
            'ETH/USDT' => 2245.80,
            'SOL/USDT' => 98.92,
        ]);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.5);

        $this->assertEquals(2, $result['total_found']);
        $this->assertEquals(0.5, $result['min_profit_filter']);
        // Only BTC/USDT and ETH/USDT should pass the 0.5% filter
    }

    public function test_applies_top_filter(): void
    {
        $commonPairs = ['BTC/USDT', 'ETH/USDT', 'SOL/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42150.50,
            'ETH/USDT' => 2234.10,
            'SOL/USDT' => 98.45,
        ]);
        $exchange2 = $this->createExchangeWithMultipleTickers('Bybit', [
            'BTC/USDT' => 42380.20,
            'ETH/USDT' => 2245.80,
            'SOL/USDT' => 98.92,
        ]);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.1, 2);

        $this->assertEquals(2, $result['total_found']);
        $this->assertEquals(2, $result['top_filter']);
        $this->assertCount(2, $result['opportunities']);
    }

    public function test_sorts_opportunities_by_profit_descending(): void
    {
        $commonPairs = ['BTC/USDT', 'ETH/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42150.50,  // 0.545% profit
            'ETH/USDT' => 2234.10,   // 0.524% profit
        ]);
        $exchange2 = $this->createExchangeWithMultipleTickers('Bybit', [
            'BTC/USDT' => 42380.20,
            'ETH/USDT' => 2245.80,
        ]);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.1);

        $opportunities = $result['opportunities'];
        $this->assertEquals('BTC/USDT', $opportunities[0]['pair']);
        $this->assertGreaterThan($opportunities[1]['profitPercent'], $opportunities[0]['profitPercent']);
    }

    public function test_continues_when_exchange_fails_for_some_pairs(): void
    {
        $commonPairs = ['BTC/USDT', 'ETH/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42150.50,
            'ETH/USDT' => 2234.10,
        ]);
        $exchange2 = $this->createExchangeWithPartialFailure('Bybit', [
            'BTC/USDT' => 42380.20,
        ], ['ETH/USDT']);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.1);

        // Should still find opportunity for BTC/USDT
        $this->assertGreaterThanOrEqual(1, $result['total_found']);
    }

    public function test_skips_pairs_with_insufficient_tickers(): void
    {
        $commonPairs = ['BTC/USDT', 'ETH/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42150.50,
        ]);
        $exchange2 = $this->createExchangeWithPartialFailure('Bybit', [
            'BTC/USDT' => 42380.20,
        ], ['ETH/USDT']);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.1);

        // ETH/USDT should be skipped (only 1 ticker available)
        $this->assertEquals(1, $result['total_found']);
    }

    public function test_returns_empty_when_no_opportunities_found(): void
    {
        $commonPairs = ['BTC/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', [
            'BTC/USDT' => 42000.00,
        ]);
        $exchange2 = $this->createExchangeWithMultipleTickers('Bybit', [
            'BTC/USDT' => 42000.00,
        ]);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute(0.1);

        $this->assertEquals(0, $result['total_found']);
        $this->assertEmpty($result['opportunities']);
    }

    public function test_throws_exception_when_no_exchanges_configured(): void
    {
        $commonPairs = ['BTC/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchanges = [];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No exchanges configured');

        $useCase->execute(0.1);
    }

    public function test_uses_default_min_profit(): void
    {
        $commonPairs = ['BTC/USDT'];
        $this->commonPairsService = $this->createCommonPairsServiceMock($commonPairs);

        $exchange1 = $this->createExchangeWithMultipleTickers('Binance', ['BTC/USDT' => 42150.50]);
        $exchange2 = $this->createExchangeWithMultipleTickers('Bybit', ['BTC/USDT' => 42380.20]);

        $exchanges = [$exchange1, $exchange2];
        $useCase = new FindArbitrageUseCase($exchanges, $this->commonPairsService, $this->arbitrageCalculator);

        $result = $useCase->execute();

        $this->assertEquals(0.1, $result['min_profit_filter']);
    }

    private function createCommonPairsServiceMock(array $pairs): CommonPairsService
    {
        $mock = $this->createMock(CommonPairsService::class);
        $mock->method('getCommonPairs')->willReturn($pairs);

        return $mock;
    }

    private function createExchangeWithMultipleTickers(string $name, array $pairPrices): ExchangeConnectorInterface
    {
        $mock = $this->createMock(ExchangeConnectorInterface::class);
        $mock->method('getName')->willReturn($name);

        $mock->method('fetchTicker')->willReturnCallback(function ($symbol) use ($name, $pairPrices) {
            if (!isset($pairPrices[$symbol])) {
                throw new \Exception("Symbol not found: {$symbol}");
            }

            $timestamp = time() * 1000;
            return new Ticker($symbol, $pairPrices[$symbol], $name, $timestamp);
        });

        return $mock;
    }

    private function createExchangeWithPartialFailure(
        string $name,
        array $successPairs,
        array $failPairs
    ): ExchangeConnectorInterface {
        $mock = $this->createMock(ExchangeConnectorInterface::class);
        $mock->method('getName')->willReturn($name);

        $mock->method('fetchTicker')->willReturnCallback(function ($symbol) use ($name, $successPairs, $failPairs) {
            if (in_array($symbol, $failPairs)) {
                throw new \Exception("Failed to fetch {$symbol}");
            }

            if (!isset($successPairs[$symbol])) {
                throw new \Exception("Symbol not found: {$symbol}");
            }

            $timestamp = time() * 1000;
            return new Ticker($symbol, $successPairs[$symbol], $name, $timestamp);
        });

        return $mock;
    }
}
