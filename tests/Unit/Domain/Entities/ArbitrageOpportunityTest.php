<?php

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\ArbitrageOpportunity;
use PHPUnit\Framework\TestCase;

class ArbitrageOpportunityTest extends TestCase
{
    public function test_can_create_arbitrage_opportunity_via_constructor(): void
    {
        $opportunity = new ArbitrageOpportunity(
            pair: 'BTC/USDT',
            buyExchange: 'Binance',
            sellExchange: 'Bybit',
            buyPrice: 42150.50,
            sellPrice: 42380.20,
            profitPercent: 0.54
        );

        $this->assertSame('BTC/USDT', $opportunity->pair);
        $this->assertSame('Binance', $opportunity->buyExchange);
        $this->assertSame('Bybit', $opportunity->sellExchange);
        $this->assertSame(42150.50, $opportunity->buyPrice);
        $this->assertSame(42380.20, $opportunity->sellPrice);
        $this->assertSame(0.54, $opportunity->profitPercent);
    }

    public function test_can_create_arbitrage_opportunity_from_array(): void
    {
        $data = [
            'pair' => 'ETH/USDT',
            'buyExchange' => 'WhiteBIT',
            'sellExchange' => 'Poloniex',
            'buyPrice' => 2234.10,
            'sellPrice' => 2245.80,
            'profitPercent' => 0.52,
        ];

        $opportunity = ArbitrageOpportunity::fromArray($data);

        $this->assertInstanceOf(ArbitrageOpportunity::class, $opportunity);
        $this->assertSame('ETH/USDT', $opportunity->pair);
        $this->assertSame('WhiteBIT', $opportunity->buyExchange);
        $this->assertSame('Poloniex', $opportunity->sellExchange);
        $this->assertSame(2234.10, $opportunity->buyPrice);
        $this->assertSame(2245.80, $opportunity->sellPrice);
        $this->assertSame(0.52, $opportunity->profitPercent);
    }

    public function test_from_array_throws_exception_when_pair_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: pair');

        ArbitrageOpportunity::fromArray([
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'buyPrice' => 100.0,
            'sellPrice' => 101.0,
            'profitPercent' => 1.0,
        ]);
    }

    public function test_from_array_throws_exception_when_buy_exchange_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: buyExchange');

        ArbitrageOpportunity::fromArray([
            'pair' => 'BTC/USDT',
            'sellExchange' => 'Bybit',
            'buyPrice' => 100.0,
            'sellPrice' => 101.0,
            'profitPercent' => 1.0,
        ]);
    }

    public function test_from_array_throws_exception_when_sell_exchange_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: sellExchange');

        ArbitrageOpportunity::fromArray([
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'buyPrice' => 100.0,
            'sellPrice' => 101.0,
            'profitPercent' => 1.0,
        ]);
    }

    public function test_from_array_throws_exception_when_buy_price_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: buyPrice');

        ArbitrageOpportunity::fromArray([
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'sellPrice' => 101.0,
            'profitPercent' => 1.0,
        ]);
    }

    public function test_from_array_throws_exception_when_sell_price_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: sellPrice');

        ArbitrageOpportunity::fromArray([
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'buyPrice' => 100.0,
            'profitPercent' => 1.0,
        ]);
    }

    public function test_from_array_throws_exception_when_profit_percent_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: profitPercent');

        ArbitrageOpportunity::fromArray([
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'buyPrice' => 100.0,
            'sellPrice' => 101.0,
        ]);
    }

    public function test_from_array_casts_buy_price_to_float(): void
    {
        $data = [
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'buyPrice' => '42150.50',
            'sellPrice' => 42380.20,
            'profitPercent' => 0.54,
        ];

        $opportunity = ArbitrageOpportunity::fromArray($data);

        $this->assertIsFloat($opportunity->buyPrice);
        $this->assertSame(42150.50, $opportunity->buyPrice);
    }

    public function test_from_array_casts_sell_price_to_float(): void
    {
        $data = [
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'buyPrice' => 42150.50,
            'sellPrice' => '42380.20',
            'profitPercent' => 0.54,
        ];

        $opportunity = ArbitrageOpportunity::fromArray($data);

        $this->assertIsFloat($opportunity->sellPrice);
        $this->assertSame(42380.20, $opportunity->sellPrice);
    }

    public function test_from_array_casts_profit_percent_to_float(): void
    {
        $data = [
            'pair' => 'BTC/USDT',
            'buyExchange' => 'Binance',
            'sellExchange' => 'Bybit',
            'buyPrice' => 42150.50,
            'sellPrice' => 42380.20,
            'profitPercent' => '0.54',
        ];

        $opportunity = ArbitrageOpportunity::fromArray($data);

        $this->assertIsFloat($opportunity->profitPercent);
        $this->assertSame(0.54, $opportunity->profitPercent);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $opportunity = new ArbitrageOpportunity(
            pair: 'SOL/USDT',
            buyExchange: 'JBEX',
            sellExchange: 'Binance',
            buyPrice: 98.45,
            sellPrice: 98.92,
            profitPercent: 0.48
        );

        $array = $opportunity->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('pair', $array);
        $this->assertArrayHasKey('buyExchange', $array);
        $this->assertArrayHasKey('sellExchange', $array);
        $this->assertArrayHasKey('buyPrice', $array);
        $this->assertArrayHasKey('sellPrice', $array);
        $this->assertArrayHasKey('profitPercent', $array);
        $this->assertSame('SOL/USDT', $array['pair']);
        $this->assertSame('JBEX', $array['buyExchange']);
        $this->assertSame('Binance', $array['sellExchange']);
        $this->assertSame(98.45, $array['buyPrice']);
        $this->assertSame(98.92, $array['sellPrice']);
        $this->assertSame(0.48, $array['profitPercent']);
    }

    public function test_arbitrage_opportunity_properties_have_correct_types(): void
    {
        $opportunity = new ArbitrageOpportunity(
            pair: 'BTC/USDT',
            buyExchange: 'Binance',
            sellExchange: 'Bybit',
            buyPrice: 42150.50,
            sellPrice: 42380.20,
            profitPercent: 0.54
        );

        $this->assertIsString($opportunity->pair);
        $this->assertIsString($opportunity->buyExchange);
        $this->assertIsString($opportunity->sellExchange);
        $this->assertIsFloat($opportunity->buyPrice);
        $this->assertIsFloat($opportunity->sellPrice);
        $this->assertIsFloat($opportunity->profitPercent);
    }
}
