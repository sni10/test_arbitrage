<?php

namespace App\Infrastructure\Connectors;

use ccxt\Exchange;

/**
 * Bybit exchange connector using CCXT library.
 *
 * Bybit specifics:
 * - Explicit separation by categories (spot/linear/inverse/option)
 * - Symbol format: BTCUSDT (no separator) in API, normalized to BTC/USDT by CCXT
 * - market.type, market.linear, market.inverse indicate market category
 *
 * All common CCXT functionality is inherited from AbstractCcxtConnector.
 */
class BybitConnector extends AbstractCcxtConnector
{
    /**
     * Create Bybit connector with injected CCXT exchange instance.
     *
     * @param Exchange $exchange CCXT Bybit exchange instance
     */
    public function __construct(Exchange $exchange)
    {
        parent::__construct($exchange, 'Bybit');
    }
}
