<?php

namespace Tests\Unit\Domain\Services;

use App\Domain\Entities\ArbitrageOpportunity;
use App\Domain\Entities\Ticker;
use App\Domain\Services\ArbitrageCalculator;
use PHPUnit\Framework\TestCase;

class ArbitrageCalculatorTest extends TestCase
{
    private ArbitrageCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ArbitrageCalculator;
    }

    public function test_calculate_profit_with_positive_profit(): void
    {
        $profit = $this->calculator->calculateProfit(42150.50, 42380.20);

        $this->assertEqualsWithDelta(0.545, $profit, 0.001);
    }

    public function test_calculate_profit_with_zero_profit(): void
    {
        $profit = $this->calculator->calculateProfit(42150.50, 42150.50);

        $this->assertSame(0.0, $profit);
    }

    public function test_calculate_profit_with_negative_profit(): void
    {
        $profit = $this->calculator->calculateProfit(42380.20, 42150.50);

        $this->assertEqualsWithDelta(-0.542, $profit, 0.001);
    }

    public function test_calculate_profit_throws_exception_when_buy_price_is_zero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Buy price must be greater than zero');

        $this->calculator->calculateProfit(0.0, 100.0);
    }

    public function test_calculate_profit_throws_exception_when_buy_price_is_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Buy price must be greater than zero');

        $this->calculator->calculateProfit(-10.0, 100.0);
    }

    public function test_calculate_profit_with_large_profit(): void
    {
        $profit = $this->calculator->calculateProfit(100.0, 200.0);

        $this->assertSame(100.0, $profit);
    }

    public function test_find_opportunities_with_multiple_pairs(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
                new Ticker('BTC/USDT', 42250.00, 'Poloniex', 1700000000000),
            ],
            'ETH/USDT' => [
                new Ticker('ETH/USDT', 2234.10, 'WhiteBIT', 1700000000000),
                new Ticker('ETH/USDT', 2245.80, 'Poloniex', 1700000000000),
            ],
            'SOL/USDT' => [
                new Ticker('SOL/USDT', 98.45, 'JBEX', 1700000000000),
                new Ticker('SOL/USDT', 98.92, 'Binance', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.1);

        $this->assertCount(3, $opportunities);
        $this->assertContainsOnlyInstancesOf(ArbitrageOpportunity::class, $opportunities);

        $this->assertSame('BTC/USDT', $opportunities[0]->pair);
        $this->assertSame('Binance', $opportunities[0]->buyExchange);
        $this->assertSame('Bybit', $opportunities[0]->sellExchange);
        $this->assertEqualsWithDelta(0.545, $opportunities[0]->profitPercent, 0.001);
    }

    public function test_find_opportunities_filters_by_min_profit(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
            ],
            'ETH/USDT' => [
                new Ticker('ETH/USDT', 2234.10, 'WhiteBIT', 1700000000000),
                new Ticker('ETH/USDT', 2236.00, 'Poloniex', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.5);

        $this->assertCount(1, $opportunities);
        $this->assertSame('BTC/USDT', $opportunities[0]->pair);
        $this->assertGreaterThanOrEqual(0.5, $opportunities[0]->profitPercent);
    }

    public function test_find_opportunities_returns_empty_when_all_below_threshold(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 42151.00, 'Bybit', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 1.0);

        $this->assertEmpty($opportunities);
    }

    public function test_find_opportunities_sorts_by_profit_descending(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
            ],
            'ETH/USDT' => [
                new Ticker('ETH/USDT', 2234.10, 'WhiteBIT', 1700000000000),
                new Ticker('ETH/USDT', 2245.80, 'Poloniex', 1700000000000),
            ],
            'SOL/USDT' => [
                new Ticker('SOL/USDT', 98.00, 'JBEX', 1700000000000),
                new Ticker('SOL/USDT', 99.00, 'Binance', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.1);

        $this->assertCount(3, $opportunities);

        for ($i = 0; $i < count($opportunities) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $opportunities[$i + 1]->profitPercent,
                $opportunities[$i]->profitPercent,
                'Opportunities should be sorted by profit descending'
            );
        }
    }

    public function test_find_opportunities_skips_pairs_with_less_than_two_tickers(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
            ],
            'ETH/USDT' => [
                new Ticker('ETH/USDT', 2234.10, 'WhiteBIT', 1700000000000),
                new Ticker('ETH/USDT', 2245.80, 'Poloniex', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.1);

        $this->assertCount(1, $opportunities);
        $this->assertSame('ETH/USDT', $opportunities[0]->pair);
    }

    public function test_find_opportunities_skips_when_min_and_max_on_same_exchange(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.1);

        $this->assertEmpty($opportunities);
    }

    public function test_find_opportunities_with_empty_input(): void
    {
        $opportunities = $this->calculator->findOpportunities([], 0.1);

        $this->assertEmpty($opportunities);
    }

    public function test_find_opportunities_ignores_invalid_ticker_elements(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                'invalid',
                new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.1);

        $this->assertCount(1, $opportunities);
        $this->assertSame('BTC/USDT', $opportunities[0]->pair);
    }

    public function test_find_opportunities_uses_default_min_profit(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 42150.50, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 42380.20, 'Bybit', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair);

        $this->assertCount(1, $opportunities);
        $this->assertGreaterThanOrEqual(0.1, $opportunities[0]->profitPercent);
    }

    public function test_find_opportunities_calculates_correct_profit_percent(): void
    {
        $tickersByPair = [
            'BTC/USDT' => [
                new Ticker('BTC/USDT', 100.0, 'Binance', 1700000000000),
                new Ticker('BTC/USDT', 150.0, 'Bybit', 1700000000000),
            ],
        ];

        $opportunities = $this->calculator->findOpportunities($tickersByPair, 0.1);

        $this->assertCount(1, $opportunities);
        $this->assertSame(50.0, $opportunities[0]->profitPercent);
        $this->assertSame(100.0, $opportunities[0]->buyPrice);
        $this->assertSame(150.0, $opportunities[0]->sellPrice);
    }
}
