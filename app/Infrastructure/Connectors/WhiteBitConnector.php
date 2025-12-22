<?php

namespace App\Infrastructure\Connectors;

use ccxt\Exchange;

/**
 * WhiteBIT exchange connector using CCXT library.
 *
 * WhiteBIT specifics:
 * - Symbol format: BTC_USDT (with underscore) in API, normalized to BTC/USDT by CCXT
 * - Some markets may lack vwap or percentage fields
 * - Check precision and limits for trading operations
 *
 * All common CCXT functionality is inherited from AbstractCcxtConnector.
 */
class WhiteBitConnector extends AbstractCcxtConnector
{
    /**
     * Create WhiteBIT connector with injected CCXT exchange instance.
     *
     * @param  Exchange  $exchange  CCXT WhiteBIT exchange instance
     */
    public function __construct(Exchange $exchange)
    {
        parent::__construct($exchange, 'WhiteBIT');
    }
}
