<?php

namespace App\Application\UseCases;

use App\Application\Services\CommonPairsService;
use App\Domain\Services\ArbitrageCalculator;

/**
 * Use case for finding arbitrage opportunities across all exchanges.
 *
 * Retrieves common trading pairs, fetches tickers from all exchanges,
 * calculates profit opportunities, and applies filters.
 */
class FindArbitrageUseCase
{
    /**
     * @param array $exchanges Array of ExchangeConnectorInterface instances
     * @param CommonPairsService $commonPairsService Service for getting common pairs
     * @param ArbitrageCalculator $arbitrageCalculator Domain service for arbitrage calculations
     */
    public function __construct(
        private array $exchanges,
        private CommonPairsService $commonPairsService,
        private ArbitrageCalculator $arbitrageCalculator
    ) {
    }

    /**
     * Execute the use case to find arbitrage opportunities.
     *
     * @param float $minProfit Minimum profit percentage threshold (default: 0.1)
     * @param int|null $top Limit results to top N opportunities (null = all)
     * @return array{
     *     opportunities: array<array>,
     *     total_found: int,
     *     pairs_checked: int,
     *     min_profit_filter: float,
     *     top_filter: int|null
     * }
     * @throws \Exception If no exchanges are available or no common pairs found
     */
    public function execute(float $minProfit = 0.1, ?int $top = null): array
    {
        if (empty($this->exchanges)) {
            throw new \Exception('No exchanges configured');
        }

        // Get common pairs (cached)
        $commonPairs = $this->commonPairsService->getCommonPairs();

        // Fetch tickers for all common pairs from all exchanges
        $tickersByPair = $this->fetchTickersForPairs($commonPairs);

        // Find arbitrage opportunities using domain service
        $opportunities = $this->arbitrageCalculator->findOpportunities($tickersByPair, $minProfit);

        // Apply top filter if specified
        if ($top !== null && $top > 0) {
            $opportunities = array_slice($opportunities, 0, $top);
        }

        // Convert opportunities to array format
        $opportunitiesArray = array_map(
            fn($opp) => $opp->toArray(),
            $opportunities
        );

        return [
            'opportunities' => $opportunitiesArray,
            'total_found' => count($opportunities),
            'pairs_checked' => count($commonPairs),
            'min_profit_filter' => $minProfit,
            'top_filter' => $top,
        ];
    }

    /**
     * Fetch tickers for multiple pairs from all exchanges.
     *
     * Implements graceful degradation - continues if some exchanges fail.
     *
     * @param array<string> $pairs Array of trading pair symbols
     * @return array<string, array<Ticker>> Tickers grouped by pair symbol
     */
    private function fetchTickersForPairs(array $pairs): array
    {
        $tickersByPair = [];

        foreach ($pairs as $pair) {
            $tickers = [];

            foreach ($this->exchanges as $exchange) {
                try {
                    $ticker = $exchange->fetchTicker($pair);
                    $tickers[] = $ticker;
                } catch (\Exception $e) {
                    // Continue with other exchanges (graceful degradation)
                }
            }

            // Only include pairs with at least 2 tickers (needed for arbitrage)
            if (count($tickers) >= 2) {
                $tickersByPair[$pair] = $tickers;
            }
        }

        return $tickersByPair;
    }
}
