<?php

namespace App\Infrastructure\Connectors;

use ccxt\Exchange;

/**
 * Binance exchange connector using CCXT library.
 *
 * Binance specifics:
 * - Symbol format: BTCUSDT (no separator) in API, normalized to BTC/USDT by CCXT
 * - Supports spot, margin, and futures markets
 * - High liquidity and reliable API
 *
 * All common CCXT functionality is inherited from AbstractCcxtConnector.
 */
class BinanceConnector extends AbstractCcxtConnector
{
    /**
     * Create Binance connector with injected CCXT exchange instance.
     *
     * @param  Exchange  $exchange  CCXT Binance exchange instance
     */
    public function __construct(Exchange $exchange)
    {
        parent::__construct($exchange, 'Binance');
    }
}
