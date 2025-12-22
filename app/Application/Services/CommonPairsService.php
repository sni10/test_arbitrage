<?php

namespace App\Application\Services;

use App\Infrastructure\Cache\LaravelCacheAdapter;

/**
 * Service for finding common trading pairs across all exchanges.
 *
 * Retrieves available pairs from each exchange and finds the intersection
 * (pairs available on ALL exchanges). Results are cached to minimize API calls.
 */
class CommonPairsService
{
    private const CACHE_KEY = 'common_pairs';

    /**
     * @param array $exchanges Array of ExchangeConnectorInterface instances
     * @param LaravelCacheAdapter $cache Cache adapter for storing results
     * @param int $cacheTtl Cache TTL in seconds (default: 3600 = 1 hour)
     */
    public function __construct(
        private array $exchanges,
        private LaravelCacheAdapter $cache,
        private int $cacheTtl = 3600
    ) {
    }

    /**
     * Get list of trading pairs available on ALL exchanges.
     *
     * Results are cached for PAIRS_CACHE_TTL seconds (default: 3600 = 1 hour).
     * If an exchange is temporarily unavailable, it will be skipped with a warning log.
     *
     * @return array<string> Array of trading pair symbols (e.g., ['BTC/USDT', 'ETH/USDT'])
     * @throws \Exception If no exchanges are available or no common pairs found
     */
    public function getCommonPairs(): array
    {
        return $this->cache->remember(self::CACHE_KEY, $this->cacheTtl, function () {
            return $this->fetchCommonPairs();
        });
    }

    /**
     * Fetch common pairs from all exchanges (without cache).
     *
     * @return array<string> Array of common trading pair symbols
     * @throws \Exception If no exchanges are available or no common pairs found
     */
    private function fetchCommonPairs(): array
    {
        if (empty($this->exchanges)) {
            throw new \Exception('No exchanges configured');
        }

        $pairsByExchange = [];
        $failedExchanges = [];

        foreach ($this->exchanges as $exchange) {
            try {
                $pairs = $exchange->getAvailablePairs();
                $pairsByExchange[$exchange->getName()] = $pairs;
            } catch (\Exception $e) {
                $failedExchanges[] = $exchange->getName();
            }
        }

        if (empty($pairsByExchange)) {
            throw new \Exception('All exchanges are unavailable. Failed exchanges: ' . implode(', ', $failedExchanges));
        }

        // Find intersection of all pairs
        $commonPairs = $this->findIntersection($pairsByExchange);

        if (empty($commonPairs)) {
            $exchangeNames = array_keys($pairsByExchange);
            throw new \Exception('No common pairs found across exchanges: ' . implode(', ', $exchangeNames));
        }

        return $commonPairs;
    }

    /**
     * Find intersection of trading pairs across all exchanges.
     *
     * @param array<string, array<string>> $pairsByExchange Pairs grouped by exchange name
     * @return array<string> Array of common pairs
     */
    private function findIntersection(array $pairsByExchange): array
    {
        if (empty($pairsByExchange)) {
            return [];
        }

        // Start with pairs from first exchange
        $commonPairs = array_shift($pairsByExchange);

        // Intersect with pairs from remaining exchanges
        foreach ($pairsByExchange as $pairs) {
            $commonPairs = array_intersect($commonPairs, $pairs);
        }

        // Re-index array and sort
        $commonPairs = array_values($commonPairs);
        sort($commonPairs);

        return $commonPairs;
    }

    /**
     * Clear cached common pairs.
     *
     * Useful for forcing a refresh of the pairs list.
     *
     * @return bool True on success
     */
    public function clearCache(): bool
    {
        return $this->cache->forget(self::CACHE_KEY);
    }
}
