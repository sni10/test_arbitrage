<?php

namespace App\Domain\Contracts;

/**
 * Cache abstraction interface.
 *
 * Provides domain layer with cache capabilities without depending on
 * specific infrastructure implementations (Laravel Cache, Redis, etc.).
 * Follows Dependency Inversion Principle (DIP).
 */
interface CacheInterface
{
    /**
     * Retrieve an item from the cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if key doesn't exist
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache.
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|null  $ttl  Time to live in seconds (null = forever)
     * @return bool True on success
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Get an item from cache or execute callback and store result.
     *
     * @param  string  $key  Cache key
     * @param  int  $ttl  Time to live in seconds
     * @param  callable  $callback  Callback to execute if cache miss
     * @return mixed Cached or computed value
     */
    public function remember(string $key, int $ttl, callable $callback): mixed;

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key  Cache key
     * @return bool True on success
     */
    public function forget(string $key): bool;

    /**
     * Check if an item exists in the cache.
     *
     * @param  string  $key  Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;
}
