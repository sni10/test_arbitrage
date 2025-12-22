<?php

namespace App\Domain\Entities;

/**
 * ArbitrageOpportunity entity (DTO).
 *
 * Represents a potential arbitrage opportunity between two exchanges.
 * Immutable value object following Domain-Driven Design principles.
 */
readonly class ArbitrageOpportunity
{
    /**
     * @param string $pair Trading pair in BASE/QUOTE format (e.g., 'BTC/USDT')
     * @param string $buyExchange Exchange to buy from (lower price)
     * @param string $sellExchange Exchange to sell on (higher price)
     * @param float $buyPrice Price on buy exchange
     * @param float $sellPrice Price on sell exchange
     * @param float $profitPercent Profit percentage: ((sellPrice - buyPrice) / buyPrice) * 100
     */
    public function __construct(
        public string $pair,
        public string $buyExchange,
        public string $sellExchange,
        public float $buyPrice,
        public float $sellPrice,
        public float $profitPercent
    ) {
    }

    /**
     * Create ArbitrageOpportunity from array data.
     *
     * @param array $data Associative array with required keys
     * @return self
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        $required = ['pair', 'buyExchange', 'sellExchange', 'buyPrice', 'sellPrice', 'profitPercent'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        return new self(
            pair: $data['pair'],
            buyExchange: $data['buyExchange'],
            sellExchange: $data['sellExchange'],
            buyPrice: (float) $data['buyPrice'],
            sellPrice: (float) $data['sellPrice'],
            profitPercent: (float) $data['profitPercent']
        );
    }

    /**
     * Convert ArbitrageOpportunity to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'pair' => $this->pair,
            'buyExchange' => $this->buyExchange,
            'sellExchange' => $this->sellExchange,
            'buyPrice' => $this->buyPrice,
            'sellPrice' => $this->sellPrice,
            'profitPercent' => $this->profitPercent,
        ];
    }
}
