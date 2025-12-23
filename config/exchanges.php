<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exchange Connectors Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for all supported cryptocurrency exchanges.
    | Each exchange can be enabled/disabled individually.
    |
    */

    'binance' => [
        'name' => 'Binance',
        'api_url' => 'https://api.binance.com',
        'enabled' => env('BINANCE_ENABLED', true),
        'type' => 'ccxt',
    ],

    'jbex' => [
        'name' => 'JBEX',
        'api_url' => env('JBEX_API_URL', 'https://api.jucoin.com'),
        'api_key' => env('JBEX_API_KEY', ''),
        'enabled' => env('JBEX_ENABLED', true),
        'type' => 'custom',
        'endpoints' => [
            'broker_info' => '/v1/spot/public/symbol',
            'ticker_price' => '/v1/spot/public/ticker/price',
            'book_ticker' => '/v1/spot/public/ticker/book',
            'depth' => '/v1/spot/public/depth',
            'trades' => '/v1/spot/public/trade/recent',
            'klines' => '/v1/spot/public/kline',
        ],
    ],

    'poloniex' => [
        'name' => 'Poloniex',
        'api_url' => 'https://api.poloniex.com',
        'enabled' => env('POLONIEX_ENABLED', true),
        'type' => 'ccxt',
    ],

    'bybit' => [
        'name' => 'Bybit',
        'api_url' => 'https://api.bybit.com',
        'enabled' => env('BYBIT_ENABLED', true),
        'type' => 'ccxt',
    ],

    'whitebit' => [
        'name' => 'WhiteBIT',
        'api_url' => 'https://whitebit.com',
        'enabled' => env('WHITEBIT_ENABLED', true),
        'type' => 'ccxt',
    ],

];
