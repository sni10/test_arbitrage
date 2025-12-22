<?php

namespace Tests\Feature\Commands;

use App\Application\UseCases\GetBestPriceUseCase;
use Tests\TestCase;

/**
 * Feature tests for GetBestPriceCommand (arb:price).
 *
 * Tests command execution with mocked use case to avoid real API calls.
 */
class GetBestPriceCommandTest extends TestCase
{

    /**
     * Test successful command execution with valid pair.
     */
    public function test_successful_execution_with_valid_pair(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(GetBestPriceUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with('BTC/USDT')
            ->willReturn([
                'pair' => 'BTC/USDT',
                'min' => [
                    'exchange' => 'Binance',
                    'price' => 45000.50,
                    'timestamp' => time(),
                ],
                'max' => [
                    'exchange' => 'Bybit',
                    'price' => 45100.75,
                    'timestamp' => time(),
                ],
                'difference' => [
                    'absolute' => 100.25,
                    'percent' => 0.22,
                ],
                'exchanges_checked' => 5,
                'exchanges_failed' => [],
            ]);

        // Bind mock to container
        $this->app->instance(GetBestPriceUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:price', ['pair' => 'BTC/USDT'])
            ->expectsOutput('Fetching prices for BTC/USDT...')
            ->assertExitCode(0);
    }

    /**
     * Test command with invalid pair format.
     */
    public function test_invalid_pair_format(): void
    {
        // Mock the use case (won't be called, but needed for DI resolution)
        $mockUseCase = $this->createMock(GetBestPriceUseCase::class);
        $mockUseCase->expects($this->never())->method('execute');

        // Bind mock to container
        $this->app->instance(GetBestPriceUseCase::class, $mockUseCase);

        // Test various invalid formats
        $invalidPairs = [
            'BTCUSDT',      // Missing slash
            'BTC-USDT',     // Wrong separator
            'BTC',          // Missing quote
            'BTC/U',        // Quote too short
            'B/USDT',       // Base too short
            'BTC/USDT/ETH', // Too many parts
            '',             // Empty
        ];

        foreach ($invalidPairs as $pair) {
            $this->artisan('arb:price', ['pair' => $pair])
                ->expectsOutput("Invalid pair format: '{$pair}'")
                ->expectsOutput('Expected format: BASE/QUOTE (e.g., BTC/USDT, ETH/BTC)')
                ->assertExitCode(1);
        }
    }

    /**
     * Test command when pair is not found on any exchange.
     */
    public function test_pair_not_found_on_any_exchange(): void
    {
        // Mock the use case to throw exception
        $mockUseCase = $this->createMock(GetBestPriceUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with('INVALID/PAIR')
            ->willThrowException(new \Exception("Trading pair 'INVALID/PAIR' not found on any exchange"));

        // Bind mock to container
        $this->app->instance(GetBestPriceUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:price', ['pair' => 'INVALID/PAIR'])
            ->expectsOutput("Error: Trading pair 'INVALID/PAIR' not found on any exchange")
            ->assertExitCode(1);
    }

    /**
     * Test command with graceful degradation (some exchanges failed).
     */
    public function test_graceful_degradation_with_failed_exchanges(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(GetBestPriceUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with('ETH/USDT')
            ->willReturn([
                'pair' => 'ETH/USDT',
                'min' => [
                    'exchange' => 'Binance',
                    'price' => 2500.00,
                    'timestamp' => time(),
                ],
                'max' => [
                    'exchange' => 'Poloniex',
                    'price' => 2510.50,
                    'timestamp' => time(),
                ],
                'difference' => [
                    'absolute' => 10.50,
                    'percent' => 0.42,
                ],
                'exchanges_checked' => 3,
                'exchanges_failed' => ['JBEX', 'WhiteBIT'],
            ]);

        // Bind mock to container
        $this->app->instance(GetBestPriceUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:price', ['pair' => 'ETH/USDT'])
            ->assertExitCode(0);
    }

    /**
     * Test command when no exchanges are configured.
     */
    public function test_no_exchanges_configured(): void
    {
        // Mock the use case to throw exception
        $mockUseCase = $this->createMock(GetBestPriceUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with('BTC/USDT')
            ->willThrowException(new \Exception('No exchanges configured'));

        // Bind mock to container
        $this->app->instance(GetBestPriceUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:price', ['pair' => 'BTC/USDT'])
            ->expectsOutput('Error: No exchanges configured')
            ->assertExitCode(1);
    }

    /**
     * Test command with various valid pair formats.
     */
    public function test_various_valid_pair_formats(): void
    {
        $validPairs = [
            'BTC/USDT',
            'ETH/BTC',
            'DOGE/USDT',
            'btc/usdt',  // lowercase
            'BtC/UsDt',  // mixed case
        ];

        foreach ($validPairs as $pair) {
            // Mock the use case
            $mockUseCase = $this->createMock(GetBestPriceUseCase::class);
            $mockUseCase->expects($this->once())
                ->method('execute')
                ->with($pair)
                ->willReturn([
                    'pair' => $pair,
                    'min' => [
                        'exchange' => 'Binance',
                        'price' => 100.00,
                        'timestamp' => time(),
                    ],
                    'max' => [
                        'exchange' => 'Bybit',
                        'price' => 101.00,
                        'timestamp' => time(),
                    ],
                    'difference' => [
                        'absolute' => 1.00,
                        'percent' => 1.00,
                    ],
                    'exchanges_checked' => 5,
                    'exchanges_failed' => [],
                ]);

            // Bind mock to container
            $this->app->instance(GetBestPriceUseCase::class, $mockUseCase);

            // Execute command
            $this->artisan('arb:price', ['pair' => $pair])
                ->assertExitCode(0);
        }
    }
}
