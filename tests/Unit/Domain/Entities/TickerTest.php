<?php

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Ticker;
use PHPUnit\Framework\TestCase;

class TickerTest extends TestCase
{
    public function test_can_create_ticker_via_constructor(): void
    {
        $ticker = new Ticker(
            symbol: 'BTC/USDT',
            price: 42150.50,
            exchange: 'Binance',
            timestamp: 1700000000000
        );

        $this->assertSame('BTC/USDT', $ticker->symbol);
        $this->assertSame(42150.50, $ticker->price);
        $this->assertSame('Binance', $ticker->exchange);
        $this->assertSame(1700000000000, $ticker->timestamp);
    }

    public function test_can_create_ticker_from_array(): void
    {
        $data = [
            'symbol' => 'ETH/USDT',
            'price' => 2234.10,
            'exchange' => 'Bybit',
            'timestamp' => 1700000001000,
        ];

        $ticker = Ticker::fromArray($data);

        $this->assertInstanceOf(Ticker::class, $ticker);
        $this->assertSame('ETH/USDT', $ticker->symbol);
        $this->assertSame(2234.10, $ticker->price);
        $this->assertSame('Bybit', $ticker->exchange);
        $this->assertSame(1700000001000, $ticker->timestamp);
    }

    public function test_from_array_throws_exception_when_symbol_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields for Ticker');

        Ticker::fromArray([
            'price' => 100.0,
            'exchange' => 'Test',
            'timestamp' => 1700000000000,
        ]);
    }

    public function test_from_array_throws_exception_when_price_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields for Ticker');

        Ticker::fromArray([
            'symbol' => 'BTC/USDT',
            'exchange' => 'Test',
            'timestamp' => 1700000000000,
        ]);
    }

    public function test_from_array_throws_exception_when_exchange_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields for Ticker');

        Ticker::fromArray([
            'symbol' => 'BTC/USDT',
            'price' => 100.0,
            'timestamp' => 1700000000000,
        ]);
    }

    public function test_from_array_throws_exception_when_timestamp_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields for Ticker');

        Ticker::fromArray([
            'symbol' => 'BTC/USDT',
            'price' => 100.0,
            'exchange' => 'Test',
        ]);
    }

    public function test_from_array_casts_price_to_float(): void
    {
        $data = [
            'symbol' => 'BTC/USDT',
            'price' => '42150.50',
            'exchange' => 'Binance',
            'timestamp' => 1700000000000,
        ];

        $ticker = Ticker::fromArray($data);

        $this->assertIsFloat($ticker->price);
        $this->assertSame(42150.50, $ticker->price);
    }

    public function test_from_array_casts_timestamp_to_int(): void
    {
        $data = [
            'symbol' => 'BTC/USDT',
            'price' => 42150.50,
            'exchange' => 'Binance',
            'timestamp' => '1700000000000',
        ];

        $ticker = Ticker::fromArray($data);

        $this->assertIsInt($ticker->timestamp);
        $this->assertSame(1700000000000, $ticker->timestamp);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $ticker = new Ticker(
            symbol: 'SOL/USDT',
            price: 98.45,
            exchange: 'JBEX',
            timestamp: 1700000002000
        );

        $array = $ticker->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('symbol', $array);
        $this->assertArrayHasKey('price', $array);
        $this->assertArrayHasKey('exchange', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertSame('SOL/USDT', $array['symbol']);
        $this->assertSame(98.45, $array['price']);
        $this->assertSame('JBEX', $array['exchange']);
        $this->assertSame(1700000002000, $array['timestamp']);
    }

    public function test_ticker_properties_have_correct_types(): void
    {
        $ticker = new Ticker(
            symbol: 'BTC/USDT',
            price: 42150.50,
            exchange: 'Binance',
            timestamp: 1700000000000
        );

        $this->assertIsString($ticker->symbol);
        $this->assertIsFloat($ticker->price);
        $this->assertIsString($ticker->exchange);
        $this->assertIsInt($ticker->timestamp);
    }
}
