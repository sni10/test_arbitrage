<?php

namespace Tests\Unit\Domain\Services;

use App\Domain\Entities\Ticker;
use App\Domain\Services\PriceAnalyzer;
use PHPUnit\Framework\TestCase;

class PriceAnalyzerTest extends TestCase
{
    private PriceAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new PriceAnalyzer();
    }

    public function test_find_min_max_prices_with_multiple_tickers(): void
    {
        $tickers = [
            new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
            new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
            new Ticker('BTC/USDT', 42250.00, 'Poloniex', 1700000000000),
            new Ticker('BTC/USDT', 42300.00, 'WhiteBIT', 1700000000000),
            new Ticker('BTC/USDT', 42200.00, 'JBEX', 1700000000000),
        ];

        $result = $this->analyzer->findMinMaxPrices($tickers);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('min', $result);
        $this->assertArrayHasKey('max', $result);
        $this->assertInstanceOf(Ticker::class, $result['min']);
        $this->assertInstanceOf(Ticker::class, $result['max']);
        $this->assertSame(42150.50, $result['min']->price);
        $this->assertSame('Binance', $result['min']->exchange);
        $this->assertSame(42380.20, $result['max']->price);
        $this->assertSame('Bybit', $result['max']->exchange);
    }

    public function test_find_min_max_prices_with_single_ticker(): void
    {
        $tickers = [
            new Ticker('ETH/USDT', 2234.10, 'Binance', 1700000000000),
        ];

        $result = $this->analyzer->findMinMaxPrices($tickers);

        $this->assertSame($tickers[0], $result['min']);
        $this->assertSame($tickers[0], $result['max']);
        $this->assertSame(2234.10, $result['min']->price);
        $this->assertSame(2234.10, $result['max']->price);
    }

    public function test_find_min_max_prices_with_same_prices(): void
    {
        $tickers = [
            new Ticker('BTC/USDT', 42000.00, 'Binance', 1700000000000),
            new Ticker('BTC/USDT', 42000.00, 'Bybit', 1700000000000),
            new Ticker('BTC/USDT', 42000.00, 'Poloniex', 1700000000000),
        ];

        $result = $this->analyzer->findMinMaxPrices($tickers);

        $this->assertSame(42000.00, $result['min']->price);
        $this->assertSame(42000.00, $result['max']->price);
    }

    public function test_find_min_max_prices_throws_exception_when_array_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tickers array cannot be empty');

        $this->analyzer->findMinMaxPrices([]);
    }

    public function test_find_min_max_prices_throws_exception_when_invalid_element(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All elements must be Ticker instances');

        $tickers = [
            new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
            'invalid',
            new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
        ];

        $this->analyzer->findMinMaxPrices($tickers);
    }

    public function test_calculate_difference_with_positive_difference(): void
    {
        $result = $this->analyzer->calculateDifference(42150.50, 42380.20);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('absolute', $result);
        $this->assertArrayHasKey('percent', $result);
        $this->assertEqualsWithDelta(229.70, $result['absolute'], 0.01);
        $this->assertEqualsWithDelta(0.545, $result['percent'], 0.001);
    }

    public function test_calculate_difference_with_zero_difference(): void
    {
        $result = $this->analyzer->calculateDifference(42150.50, 42150.50);

        $this->assertSame(0.0, $result['absolute']);
        $this->assertSame(0.0, $result['percent']);
    }

    public function test_calculate_difference_with_negative_difference(): void
    {
        $result = $this->analyzer->calculateDifference(42380.20, 42150.50);

        $this->assertEqualsWithDelta(-229.70, $result['absolute'], 0.01);
        $this->assertEqualsWithDelta(-0.542, $result['percent'], 0.001);
    }

    public function test_calculate_difference_throws_exception_when_min_is_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum price must be greater than zero');

        $this->analyzer->calculateDifference(0.0, 100.0);
    }

    public function test_calculate_difference_throws_exception_when_min_is_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum price must be greater than zero');

        $this->analyzer->calculateDifference(-10.0, 100.0);
    }

    public function test_calculate_difference_with_small_prices(): void
    {
        $result = $this->analyzer->calculateDifference(0.001, 0.002);

        $this->assertEqualsWithDelta(0.001, $result['absolute'], 0.0001);
        $this->assertEqualsWithDelta(100.0, $result['percent'], 0.01);
    }

    public function test_calculate_difference_with_large_prices(): void
    {
        $result = $this->analyzer->calculateDifference(100000.0, 101000.0);

        $this->assertEqualsWithDelta(1000.0, $result['absolute'], 0.01);
        $this->assertEqualsWithDelta(1.0, $result['percent'], 0.001);
    }
}
