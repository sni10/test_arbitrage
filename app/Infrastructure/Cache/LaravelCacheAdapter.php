<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Laravel Cache adapter.
 *
 * Provides a clean abstraction over Laravel's Cache facade
 * for use in the application layer without direct framework dependencies.
 */
class LaravelCacheAdapter
{
    /**
     * Retrieve an item from the cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if key doesn't exist
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to store
     * @param  int|null  $ttl  Time to live in seconds (null = forever)
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        if ($ttl === null) {
            return Cache::forever($key, $value);
        }

        return Cache::put($key, $value, $ttl);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  string  $key  Cache key
     * @param  int  $ttl  Time to live in seconds
     * @param  \Closure  $callback  Callback to execute if key doesn't exist
     */
    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Determine if an item exists in the cache.
     *
     * @param  string  $key  Cache key
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key  Cache key
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key  Cache key
     * @param  int  $value  Amount to increment by
     * @return int|bool New value or false on failure
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return Cache::increment($key, $value);
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key  Cache key
     * @param  int  $value  Amount to decrement by
     * @return int|bool New value or false on failure
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return Cache::decrement($key, $value);
    }
}
