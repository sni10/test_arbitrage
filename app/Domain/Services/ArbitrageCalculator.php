<?php

namespace App\Domain\Services;

use App\Domain\Entities\ArbitrageOpportunity;
use App\Domain\Entities\Ticker;

/**
 * Arbitrage calculation service.
 *
 * Provides business logic for calculating arbitrage profit and finding opportunities.
 * Pure domain service without external dependencies.
 */
class ArbitrageCalculator
{
    /**
     * Calculate profit percentage between buy and sell prices.
     *
     * Formula: ((sellPrice - buyPrice) / buyPrice) * 100
     *
     * @param  float  $buyPrice  Price to buy at (lower price)
     * @param  float  $sellPrice  Price to sell at (higher price)
     * @return float Profit percentage
     *
     * @throws \InvalidArgumentException If buy price is zero or negative
     */
    public function calculateProfit(float $buyPrice, float $sellPrice): float
    {
        if ($buyPrice <= 0) {
            throw new \InvalidArgumentException('Buy price must be greater than zero');
        }

        return (($sellPrice - $buyPrice) / $buyPrice) * 100;
    }

    /**
     * Find arbitrage opportunities from tickers grouped by trading pair.
     *
     * @param  array<string, array<Ticker>>  $tickersByPair  Tickers grouped by pair symbol
     * @param  float  $minProfit  Minimum profit percentage threshold (default: 0.1)
     * @return array<ArbitrageOpportunity> Array of arbitrage opportunities sorted by profit (descending)
     */
    public function findOpportunities(array $tickersByPair, float $minProfit = 0.1): array
    {
        $opportunities = [];

        foreach ($tickersByPair as $pair => $tickers) {
            if (count($tickers) < 2) {
                continue;
            }

            $minTicker = null;
            $maxTicker = null;

            foreach ($tickers as $ticker) {
                if (! $ticker instanceof Ticker) {
                    continue;
                }

                if ($minTicker === null || $ticker->price < $minTicker->price) {
                    $minTicker = $ticker;
                }

                if ($maxTicker === null || $ticker->price > $maxTicker->price) {
                    $maxTicker = $ticker;
                }
            }

            if ($minTicker === null || $maxTicker === null) {
                continue;
            }

            if ($minTicker->exchange === $maxTicker->exchange) {
                continue;
            }

            $profitPercent = $this->calculateProfit($minTicker->price, $maxTicker->price);

            if ($profitPercent >= $minProfit) {
                $opportunities[] = new ArbitrageOpportunity(
                    pair: $pair,
                    buyExchange: $minTicker->exchange,
                    sellExchange: $maxTicker->exchange,
                    buyPrice: $minTicker->price,
                    sellPrice: $maxTicker->price,
                    profitPercent: $profitPercent
                );
            }
        }

        usort($opportunities, fn ($a, $b) => $b->profitPercent <=> $a->profitPercent);

        return $opportunities;
    }
}
