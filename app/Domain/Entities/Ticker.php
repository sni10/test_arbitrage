<?php

namespace App\Domain\Entities;

/**
 * Ticker entity (DTO).
 *
 * Represents price data for a trading pair from a specific exchange.
 * Immutable value object following Domain-Driven Design principles.
 */
readonly class Ticker
{
    /**
     * @param  string  $symbol  Trading pair in BASE/QUOTE format (e.g., 'BTC/USDT')
     * @param  float  $price  Current price
     * @param  string  $exchange  Exchange name (e.g., 'Binance', 'JBEX')
     * @param  int  $timestamp  Unix timestamp in milliseconds
     */
    public function __construct(
        public string $symbol,
        public float $price,
        public string $exchange,
        public int $timestamp
    ) {}

    /**
     * Create Ticker from array data.
     *
     * @param  array  $data  Associative array with keys: symbol, price, exchange, timestamp
     *
     * @throws \InvalidArgumentException If required fields are missing
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['symbol'], $data['price'], $data['exchange'], $data['timestamp'])) {
            throw new \InvalidArgumentException('Missing required fields for Ticker');
        }

        return new self(
            symbol: $data['symbol'],
            price: (float) $data['price'],
            exchange: $data['exchange'],
            timestamp: (int) $data['timestamp']
        );
    }

    /**
     * Convert Ticker to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'price' => $this->price,
            'exchange' => $this->exchange,
            'timestamp' => $this->timestamp,
        ];
    }
}
