<?php

namespace App\Domain\Services;

use App\Domain\Entities\Ticker;

/**
 * Price analysis service.
 *
 * Provides business logic for finding min/max prices and calculating differences.
 * Pure domain service without external dependencies.
 */
class PriceAnalyzer
{
    /**
     * Find minimum and maximum prices from array of tickers.
     *
     * @param  array<Ticker>  $tickers  Array of Ticker entities
     * @return array{min: Ticker, max: Ticker} Array with 'min' and 'max' Ticker entities
     *
     * @throws \InvalidArgumentException If tickers array is empty
     */
    public function findMinMaxPrices(array $tickers): array
    {
        if (empty($tickers)) {
            throw new \InvalidArgumentException('Tickers array cannot be empty');
        }

        $minTicker = $tickers[0];
        $maxTicker = $tickers[0];

        foreach ($tickers as $ticker) {
            if (! $ticker instanceof Ticker) {
                throw new \InvalidArgumentException('All elements must be Ticker instances');
            }

            if ($ticker->price < $minTicker->price) {
                $minTicker = $ticker;
            }

            if ($ticker->price > $maxTicker->price) {
                $maxTicker = $ticker;
            }
        }

        return [
            'min' => $minTicker,
            'max' => $maxTicker,
        ];
    }

    /**
     * Calculate absolute and percentage difference between two prices.
     *
     * @param  float  $min  Minimum price
     * @param  float  $max  Maximum price
     * @return array{absolute: float, percent: float} Difference in absolute value and percentage
     *
     * @throws \InvalidArgumentException If min price is zero or negative
     */
    public function calculateDifference(float $min, float $max): array
    {
        if ($min <= 0) {
            throw new \InvalidArgumentException('Minimum price must be greater than zero');
        }

        $absolute = $max - $min;
        $percent = ($absolute / $min) * 100;

        return [
            'absolute' => $absolute,
            'percent' => $percent,
        ];
    }
}
