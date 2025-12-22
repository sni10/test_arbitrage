<?php

namespace App\Console\Commands;

use App\Application\UseCases\GetBestPriceUseCase;
use Illuminate\Console\Command;

/**
 * Console command for finding the best price for a trading pair across all exchanges.
 *
 * Usage: php artisan arb:price {pair}
 * Example: php artisan arb:price BTC/USDT
 */
class GetBestPriceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arb:price {pair : Trading pair in BASE/QUOTE format (e.g., BTC/USDT)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find the best price for a trading pair across all exchanges';

    /**
     * Execute the console command.
     */
    public function handle(GetBestPriceUseCase $useCase): int
    {
        $pair = $this->argument('pair');

        // Validate pair format
        if (! $this->isValidPairFormat($pair)) {
            $this->error("Invalid pair format: '{$pair}'");
            $this->info('Expected format: BASE/QUOTE (e.g., BTC/USDT, ETH/BTC)');

            return Command::FAILURE;
        }

        try {
            $this->info("Fetching prices for {$pair}...");
            $result = $useCase->execute($pair);

            $this->displayResult($result);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Validate trading pair format (BASE/QUOTE).
     *
     * @param  string  $pair  Trading pair to validate
     * @return bool True if format is valid
     */
    private function isValidPairFormat(string $pair): bool
    {
        // Format: BASE/QUOTE (e.g., BTC/USDT)
        // BASE and QUOTE should be alphanumeric, 2-10 characters
        return preg_match('/^[A-Z0-9]{2,10}\/[A-Z0-9]{2,10}$/i', $pair) === 1;
    }

    /**
     * Display formatted result to the console.
     *
     * @param  array  $result  Result from GetBestPriceUseCase
     */
    private function displayResult(array $result): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════');
        $this->line("  Best Prices for <fg=cyan>{$result['pair']}</>");
        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Min price
        $this->line('<fg=green>Lowest Price:</>');
        $this->line("  Exchange:  <fg=yellow>{$result['min']['exchange']}</>");
        $this->line("  Price:     <fg=white>{$result['min']['price']}</>");
        $this->line('  Time:      '.date('Y-m-d H:i:s', $result['min']['timestamp']));
        $this->newLine();

        // Max price
        $this->line('<fg=red>Highest Price:</>');
        $this->line("  Exchange:  <fg=yellow>{$result['max']['exchange']}</>");
        $this->line("  Price:     <fg=white>{$result['max']['price']}</>");
        $this->line('  Time:      '.date('Y-m-d H:i:s', $result['max']['timestamp']));
        $this->newLine();

        // Difference
        $this->line('<fg=magenta>Price Difference:</>');
        $this->line("  Absolute:  <fg=white>{$result['difference']['absolute']}</>");
        $this->line("  Percent:   <fg=white>{$result['difference']['percent']}%</>");
        $this->newLine();

        // Statistics
        $this->line('<fg=blue>Statistics:</>');
        $this->line("  Exchanges checked: {$result['exchanges_checked']}");

        if (! empty($result['exchanges_failed'])) {
            $this->line('  <fg=yellow>Failed exchanges: '.implode(', ', $result['exchanges_failed']).'</>');
        }

        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();
    }
}
