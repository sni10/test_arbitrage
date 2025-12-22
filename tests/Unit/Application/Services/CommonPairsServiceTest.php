<?php

namespace Tests\Unit\Application\Services;

use App\Application\Services\CommonPairsService;
use App\Domain\Contracts\ExchangeConnectorInterface;
use App\Infrastructure\Cache\LaravelCacheAdapter;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\TestCase;

class CommonPairsServiceTest extends TestCase
{
    private LaravelCacheAdapter $cache;
    private CommonPairsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->createMock(LaravelCacheAdapter::class);

        // Mock Log facade to prevent "facade root not set" errors
        Log::shouldReceive('warning')->andReturnNull();
        Log::shouldReceive('error')->andReturnNull();
    }

    public function test_returns_common_pairs_from_all_exchanges(): void
    {
        $exchange1 = $this->createExchangeMock('Binance', ['BTC/USDT', 'ETH/USDT', 'SOL/USDT']);
        $exchange2 = $this->createExchangeMock('JBEX', ['BTC/USDT', 'ETH/USDT', 'XRP/USDT']);
        $exchange3 = $this->createExchangeMock('Bybit', ['BTC/USDT', 'ETH/USDT', 'LTC/USDT']);

        $exchanges = [$exchange1, $exchange2, $exchange3];

        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new CommonPairsService($exchanges, $this->cache);
        $result = $this->service->getCommonPairs();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('BTC/USDT', $result);
        $this->assertContains('ETH/USDT', $result);
        $this->assertNotContains('SOL/USDT', $result);
        $this->assertNotContains('XRP/USDT', $result);
    }

    public function test_returns_sorted_common_pairs(): void
    {
        $exchange1 = $this->createExchangeMock('Binance', ['ETH/USDT', 'BTC/USDT', 'ADA/USDT']);
        $exchange2 = $this->createExchangeMock('JBEX', ['BTC/USDT', 'ADA/USDT', 'ETH/USDT']);

        $exchanges = [$exchange1, $exchange2];

        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new CommonPairsService($exchanges, $this->cache);
        $result = $this->service->getCommonPairs();

        $this->assertEquals(['ADA/USDT', 'BTC/USDT', 'ETH/USDT'], $result);
    }

    public function test_uses_cache_with_correct_ttl(): void
    {
        $exchange1 = $this->createExchangeMock('Binance', ['BTC/USDT']);

        $exchanges = [$exchange1];

        $this->cache->expects($this->once())
            ->method('remember')
            ->with(
                $this->equalTo('common_pairs'),
                $this->equalTo(3600),
                $this->anything()
            )
            ->willReturn(['BTC/USDT']);

        $this->service = new CommonPairsService($exchanges, $this->cache);
        $result = $this->service->getCommonPairs();

        $this->assertEquals(['BTC/USDT'], $result);
    }

    public function test_continues_when_one_exchange_fails(): void
    {
        $exchange1 = $this->createExchangeMock('Binance', ['BTC/USDT', 'ETH/USDT']);
        $exchange2 = $this->createExchangeFailureMock('JBEX', 'Connection timeout');
        $exchange3 = $this->createExchangeMock('Bybit', ['BTC/USDT', 'ETH/USDT']);

        $exchanges = [$exchange1, $exchange2, $exchange3];

        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new CommonPairsService($exchanges, $this->cache);
        $result = $this->service->getCommonPairs();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('BTC/USDT', $result);
        $this->assertContains('ETH/USDT', $result);
    }

    public function test_throws_exception_when_all_exchanges_fail(): void
    {
        $exchange1 = $this->createExchangeFailureMock('Binance', 'Connection timeout');
        $exchange2 = $this->createExchangeFailureMock('JBEX', 'API error');

        $exchanges = [$exchange1, $exchange2];

        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new CommonPairsService($exchanges, $this->cache);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All exchanges are unavailable');

        $this->service->getCommonPairs();
    }

    public function test_throws_exception_when_no_common_pairs_found(): void
    {
        $exchange1 = $this->createExchangeMock('Binance', ['BTC/USDT', 'ETH/USDT']);
        $exchange2 = $this->createExchangeMock('JBEX', ['SOL/USDT', 'XRP/USDT']);

        $exchanges = [$exchange1, $exchange2];

        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new CommonPairsService($exchanges, $this->cache);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No common pairs found');

        $this->service->getCommonPairs();
    }

    public function test_throws_exception_when_no_exchanges_configured(): void
    {
        $exchanges = [];

        $this->cache->expects($this->once())
            ->method('remember')
            ->willReturnCallback(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->service = new CommonPairsService($exchanges, $this->cache);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No exchanges configured');

        $this->service->getCommonPairs();
    }

    public function test_clear_cache_calls_forget(): void
    {
        $exchanges = [];

        $this->cache->expects($this->once())
            ->method('forget')
            ->with($this->equalTo('common_pairs'))
            ->willReturn(true);

        $this->service = new CommonPairsService($exchanges, $this->cache);
        $result = $this->service->clearCache();

        $this->assertTrue($result);
    }

    private function createExchangeMock(string $name, array $pairs): ExchangeConnectorInterface
    {
        $mock = $this->createMock(ExchangeConnectorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getAvailablePairs')->willReturn($pairs);

        return $mock;
    }

    private function createExchangeFailureMock(string $name, string $errorMessage): ExchangeConnectorInterface
    {
        $mock = $this->createMock(ExchangeConnectorInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getAvailablePairs')->willThrowException(new \Exception($errorMessage));

        return $mock;
    }
}
