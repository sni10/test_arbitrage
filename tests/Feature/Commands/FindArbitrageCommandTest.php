<?php

namespace Tests\Feature\Commands;

use App\Application\UseCases\FindArbitrageUseCase;
use Tests\TestCase;

/**
 * Feature tests for FindArbitrageCommand (arb:opportunities).
 *
 * Tests command execution with mocked use case to avoid real API calls.
 */
class FindArbitrageCommandTest extends TestCase
{

    /**
     * Test successful command execution with default options.
     */
    public function test_successful_execution_with_default_options(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(0.1, null)
            ->willReturn([
                'opportunities' => [
                    [
                        'pair' => 'BTC/USDT',
                        'buyExchange' => 'Binance',
                        'buyPrice' => 45000.00,
                        'sellExchange' => 'Bybit',
                        'sellPrice' => 45500.00,
                        'profitPercent' => 1.11,
                    ],
                    [
                        'pair' => 'ETH/USDT',
                        'buyExchange' => 'Poloniex',
                        'buyPrice' => 2500.00,
                        'sellExchange' => 'WhiteBIT',
                        'sellPrice' => 2520.00,
                        'profitPercent' => 0.80,
                    ],
                ],
                'total_found' => 2,
                'pairs_checked' => 50,
                'min_profit_filter' => 0.1,
                'top_filter' => null,
            ]);

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities')
            ->expectsOutput('Searching for arbitrage opportunities...')
            ->assertExitCode(0);
    }

    /**
     * Test command with custom min-profit option.
     */
    public function test_with_custom_min_profit(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(0.5, null)
            ->willReturn([
                'opportunities' => [
                    [
                        'pair' => 'BTC/USDT',
                        'buyExchange' => 'Binance',
                        'buyPrice' => 45000.00,
                        'sellExchange' => 'Bybit',
                        'sellPrice' => 45500.00,
                        'profitPercent' => 1.11,
                    ],
                ],
                'total_found' => 1,
                'pairs_checked' => 50,
                'min_profit_filter' => 0.5,
                'top_filter' => null,
            ]);

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities', ['--min-profit' => '0.5'])
            ->assertExitCode(0);
    }

    /**
     * Test command with top option.
     */
    public function test_with_top_option(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(0.1, 5)
            ->willReturn([
                'opportunities' => [
                    [
                        'pair' => 'BTC/USDT',
                        'buyExchange' => 'Binance',
                        'buyPrice' => 45000.00,
                        'sellExchange' => 'Bybit',
                        'sellPrice' => 45500.00,
                        'profitPercent' => 1.11,
                    ],
                ],
                'total_found' => 1,
                'pairs_checked' => 50,
                'min_profit_filter' => 0.1,
                'top_filter' => 5,
            ]);

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities', ['--top' => '5'])
            ->assertExitCode(0);
    }

    /**
     * Test command with both min-profit and top options.
     */
    public function test_with_both_options(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(1.0, 10)
            ->willReturn([
                'opportunities' => [
                    [
                        'pair' => 'BTC/USDT',
                        'buyExchange' => 'Binance',
                        'buyPrice' => 45000.00,
                        'sellExchange' => 'Bybit',
                        'sellPrice' => 45900.00,
                        'profitPercent' => 2.00,
                    ],
                ],
                'total_found' => 1,
                'pairs_checked' => 50,
                'min_profit_filter' => 1.0,
                'top_filter' => 10,
            ]);

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities', [
            '--min-profit' => '1.0',
            '--top' => '10',
        ])
            ->assertExitCode(0);
    }

    /**
     * Test command with no opportunities found.
     */
    public function test_no_opportunities_found(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(5.0, null)
            ->willReturn([
                'opportunities' => [],
                'total_found' => 0,
                'pairs_checked' => 50,
                'min_profit_filter' => 5.0,
                'top_filter' => null,
            ]);

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities', ['--min-profit' => '5.0'])
            ->expectsOutput('No arbitrage opportunities found matching the criteria.')
            ->assertExitCode(0);
    }

    /**
     * Test command with invalid min-profit (negative).
     */
    public function test_invalid_min_profit_negative(): void
    {
        // Mock the use case (won't be called, but needed for DI resolution)
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->never())->method('execute');

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        $this->artisan('arb:opportunities', ['--min-profit' => '-1'])
            ->expectsOutput('Invalid min-profit value. Must be >= 0')
            ->assertExitCode(1);
    }

    /**
     * Test command with invalid top (zero).
     */
    public function test_invalid_top_zero(): void
    {
        // Mock the use case (won't be called, but needed for DI resolution)
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->never())->method('execute');

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        $this->artisan('arb:opportunities', ['--top' => '0'])
            ->expectsOutput('Invalid top value. Must be > 0')
            ->assertExitCode(1);
    }

    /**
     * Test command with invalid top (negative).
     */
    public function test_invalid_top_negative(): void
    {
        // Mock the use case (won't be called, but needed for DI resolution)
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->never())->method('execute');

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        $this->artisan('arb:opportunities', ['--top' => '-5'])
            ->expectsOutput('Invalid top value. Must be > 0')
            ->assertExitCode(1);
    }

    /**
     * Test command when no exchanges are configured.
     */
    public function test_no_exchanges_configured(): void
    {
        // Mock the use case to throw exception
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(0.1, null)
            ->willThrowException(new \Exception('No exchanges configured'));

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities')
            ->expectsOutput('Error: No exchanges configured')
            ->assertExitCode(1);
    }

    /**
     * Test command with multiple opportunities (table output).
     */
    public function test_multiple_opportunities_table_output(): void
    {
        // Mock the use case
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(0.1, null)
            ->willReturn([
                'opportunities' => [
                    [
                        'pair' => 'BTC/USDT',
                        'buyExchange' => 'Binance',
                        'buyPrice' => 45000.12345678,
                        'sellExchange' => 'Bybit',
                        'sellPrice' => 45500.87654321,
                        'profitPercent' => 1.1134,
                    ],
                    [
                        'pair' => 'ETH/USDT',
                        'buyExchange' => 'Poloniex',
                        'buyPrice' => 2500.00,
                        'sellExchange' => 'WhiteBIT',
                        'sellPrice' => 2520.00,
                        'profitPercent' => 0.80,
                    ],
                    [
                        'pair' => 'DOGE/USDT',
                        'buyExchange' => 'JBEX',
                        'buyPrice' => 0.12345678,
                        'sellExchange' => 'Binance',
                        'sellPrice' => 0.12456789,
                        'profitPercent' => 0.90,
                    ],
                ],
                'total_found' => 3,
                'pairs_checked' => 50,
                'min_profit_filter' => 0.1,
                'top_filter' => null,
            ]);

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities')
            ->expectsOutput('Searching for arbitrage opportunities...')
            ->assertExitCode(0);
    }

    /**
     * Test command with general exception.
     */
    public function test_general_exception_handling(): void
    {
        // Mock the use case to throw exception
        $mockUseCase = $this->createMock(FindArbitrageUseCase::class);
        $mockUseCase->expects($this->once())
            ->method('execute')
            ->with(0.1, null)
            ->willThrowException(new \Exception('Unexpected error occurred'));

        // Bind mock to container
        $this->app->instance(FindArbitrageUseCase::class, $mockUseCase);

        // Execute command
        $this->artisan('arb:opportunities')
            ->expectsOutput('Error: Unexpected error occurred')
            ->assertExitCode(1);
    }
}
