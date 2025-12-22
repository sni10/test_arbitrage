<?php

namespace App\Application\UseCases;

use App\Domain\Services\PriceAnalyzer;
use Illuminate\Support\Facades\Log;

/**
 * Use case for finding the best price for a trading pair across all exchanges.
 *
 * Fetches ticker data from all exchanges and identifies the minimum and maximum prices.
 * Implements graceful degradation - continues if some exchanges are unavailable.
 */
class GetBestPriceUseCase
{
    /**
     * @param  array  $exchanges  Array of ExchangeConnectorInterface instances
     * @param  PriceAnalyzer  $priceAnalyzer  Domain service for price analysis
     */
    public function __construct(
        private array $exchanges,
        private PriceAnalyzer $priceAnalyzer
    ) {}

    /**
     * Execute the use case to find best prices for a trading pair.
     *
     * @param  string  $pair  Trading pair in BASE/QUOTE format (e.g., 'BTC/USDT')
     * @return array{
     *     pair: string,
     *     min: array{exchange: string, price: float, timestamp: int},
     *     max: array{exchange: string, price: float, timestamp: int},
     *     difference: array{absolute: float, percent: float},
     *     exchanges_checked: int,
     *     exchanges_failed: array<string>
     * }
     *
     * @throws \Exception If no exchanges are available or pair is not found on any exchange
     */
    public function execute(string $pair): array
    {
        if (empty($this->exchanges)) {
            throw new \Exception('No exchanges configured');
        }

        $tickers = [];
        $failedExchanges = [];

        // Fetch tickers from all exchanges
        foreach ($this->exchanges as $exchange) {
            try {
                $ticker = $exchange->fetchTicker($pair);
                $tickers[] = $ticker;
            } catch (\Exception $e) {
                $exchangeName = $exchange->getName();
                $failedExchanges[] = $exchangeName;
                Log::warning("Failed to fetch ticker for {$pair} from {$exchangeName}: {$e->getMessage()}");
            }
        }

        // Check if we have any tickers
        if (empty($tickers)) {
            $message = "Trading pair '{$pair}' not found on any exchange";
            if (! empty($failedExchanges)) {
                $message .= '. Failed exchanges: '.implode(', ', $failedExchanges);
            }
            throw new \Exception($message);
        }

        // Find min/max prices using domain service
        $minMax = $this->priceAnalyzer->findMinMaxPrices($tickers);
        $minTicker = $minMax['min'];
        $maxTicker = $minMax['max'];

        // Calculate difference
        $difference = $this->priceAnalyzer->calculateDifference(
            $minTicker->price,
            $maxTicker->price
        );

        return [
            'pair' => $pair,
            'min' => [
                'exchange' => $minTicker->exchange,
                'price' => $minTicker->price,
                'timestamp' => $minTicker->timestamp,
            ],
            'max' => [
                'exchange' => $maxTicker->exchange,
                'price' => $maxTicker->price,
                'timestamp' => $maxTicker->timestamp,
            ],
            'difference' => [
                'absolute' => $difference['absolute'],
                'percent' => $difference['percent'],
            ],
            'exchanges_checked' => count($tickers),
            'exchanges_failed' => $failedExchanges,
        ];
    }
}
