<?php

namespace Tests\Unit\Infrastructure\Cache;

use App\Infrastructure\Cache\LaravelCacheAdapter;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LaravelCacheAdapterTest extends TestCase
{
    public function test_get_delegates_to_cache(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('example', 'fallback')
            ->andReturn('value');

        $adapter = new LaravelCacheAdapter;

        $this->assertSame('value', $adapter->get('example', 'fallback'));
    }

    public function test_put_uses_forever_when_ttl_is_null(): void
    {
        Cache::shouldReceive('forever')
            ->once()
            ->with('key', 'value')
            ->andReturn(true);

        $adapter = new LaravelCacheAdapter;

        $this->assertTrue($adapter->put('key', 'value'));
    }

    public function test_put_uses_ttl_when_provided(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->with('key', 'value', 120)
            ->andReturn(true);

        $adapter = new LaravelCacheAdapter;

        $this->assertTrue($adapter->put('key', 'value', 120));
    }

    public function test_remember_delegates_to_cache(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('remember-key', 60, \Mockery::type(\Closure::class))
            ->andReturn('remembered');

        $adapter = new LaravelCacheAdapter;

        $this->assertSame('remembered', $adapter->remember('remember-key', 60, fn () => 'value'));
    }

    public function test_has_forget_and_flush_delegate_to_cache(): void
    {
        Cache::shouldReceive('has')
            ->once()
            ->with('exists')
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('exists')
            ->andReturn(true);

        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $adapter = new LaravelCacheAdapter;

        $this->assertTrue($adapter->has('exists'));
        $this->assertTrue($adapter->forget('exists'));
        $this->assertTrue($adapter->flush());
    }

    public function test_increment_and_decrement_delegate_to_cache(): void
    {
        Cache::shouldReceive('increment')
            ->once()
            ->with('counter', 2)
            ->andReturn(4);

        Cache::shouldReceive('decrement')
            ->once()
            ->with('counter', 1)
            ->andReturn(3);

        $adapter = new LaravelCacheAdapter;

        $this->assertSame(4, $adapter->increment('counter', 2));
        $this->assertSame(3, $adapter->decrement('counter'));
    }
}
