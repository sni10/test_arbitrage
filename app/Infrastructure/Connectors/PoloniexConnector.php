<?php

namespace App\Infrastructure\Connectors;

use ccxt\Exchange;

/**
 * Poloniex exchange connector using CCXT library.
 *
 * Poloniex specifics:
 * - Symbol format: USDT_BTC (inverted with underscore) in API, normalized to BTC/USDT by CCXT
 * - Not all markets are active - filtered by active flag
 * - Volume fields may vary - rely on baseVolume and quoteVolume
 *
 * All common CCXT functionality is inherited from AbstractCcxtConnector.
 */
class PoloniexConnector extends AbstractCcxtConnector
{
    /**
     * Create Poloniex connector with injected CCXT exchange instance.
     *
     * @param  Exchange  $exchange  CCXT Poloniex exchange instance
     */
    public function __construct(Exchange $exchange)
    {
        parent::__construct($exchange, 'Poloniex');
    }
}
