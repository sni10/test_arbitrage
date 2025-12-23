<?php

namespace Tests\Unit\Infrastructure\Factories;

use App\Infrastructure\Factories\CcxtClientFactory;
use Tests\TestCase;

class CcxtClientFactoryTest extends TestCase
{
    public function test_create_binance_returns_exchange_instance(): void
    {
        $factory = new CcxtClientFactory;
        $exchange = $factory->createBinance();

        $this->assertInstanceOf(\ccxt\binance::class, $exchange);
    }

    public function test_create_poloniex_returns_exchange_instance(): void
    {
        $factory = new CcxtClientFactory;
        $exchange = $factory->createPoloniex();

        $this->assertInstanceOf(\ccxt\poloniex::class, $exchange);
    }

    public function test_create_bybit_returns_exchange_instance(): void
    {
        $factory = new CcxtClientFactory;
        $exchange = $factory->createBybit();

        $this->assertInstanceOf(\ccxt\bybit::class, $exchange);
    }

    public function test_create_whitebit_returns_exchange_instance(): void
    {
        $factory = new CcxtClientFactory;
        $exchange = $factory->createWhitebit();

        $this->assertInstanceOf(\ccxt\whitebit::class, $exchange);
    }

    public function test_create_throws_when_exchange_class_missing(): void
    {
        $factory = new CcxtClientFactory;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CCXT exchange class not found');

        $factory->create('does_not_exist');
    }
}
