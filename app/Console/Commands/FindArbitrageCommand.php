<?php

namespace App\Console\Commands;

use App\Application\UseCases\FindArbitrageUseCase;
use Illuminate\Console\Command;

/**
 * Console command for finding arbitrage opportunities across all exchanges.
 *
 * Usage: php artisan arb:opportunities {--min-profit=0.1} {--top=}
 * Example: php artisan arb:opportunities --min-profit=0.5 --top=10
 */
class FindArbitrageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arb:opportunities
                            {--min-profit=0.1 : Minimum profit percentage threshold}
                            {--top= : Limit results to top N opportunities}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find arbitrage opportunities across all exchanges';

    /**
     * Execute the console command.
     */
    public function handle(FindArbitrageUseCase $useCase): int
    {
        $minProfit = (float) $this->option('min-profit');
        $topOption = $this->option('top');
        $top = $topOption !== null ? (int) $topOption : null;

        // Validate min-profit
        if ($minProfit < 0) {
            $this->error('Invalid min-profit value. Must be >= 0');

            return Command::FAILURE;
        }

        // Validate top
        if ($top !== null && $top <= 0) {
            $this->error('Invalid top value. Must be > 0');

            return Command::FAILURE;
        }

        try {
            $this->info('Searching for arbitrage opportunities...');
            $result = $useCase->execute($minProfit, $top);

            $this->displayResult($result);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Display formatted result to the console.
     *
     * @param  array  $result  Result from FindArbitrageUseCase
     */
    private function displayResult(array $result): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════');
        $this->line('  Arbitrage Opportunities');
        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Display filters
        $this->line('<fg=blue>Filters Applied:</>');
        $this->line("  Min Profit:    {$result['min_profit_filter']}%");
        $this->line('  Top Results:   '.($result['top_filter'] ?? 'All'));
        $this->line("  Pairs Checked: {$result['pairs_checked']}");
        $this->newLine();

        // Check if opportunities found
        if ($result['total_found'] === 0) {
            $this->warn('No arbitrage opportunities found matching the criteria.');
            $this->line('═══════════════════════════════════════════════════════');
            $this->newLine();

            return;
        }

        // Display opportunities in table format
        $this->line("<fg=green>Found {$result['total_found']} opportunities:</>");
        $this->newLine();

        $tableData = [];
        foreach ($result['opportunities'] as $opp) {
            $tableData[] = [
                $opp['pair'],
                $opp['buyExchange'],
                number_format($opp['buyPrice'], 8),
                $opp['sellExchange'],
                number_format($opp['sellPrice'], 8),
                number_format($opp['profitPercent'], 2).'%',
            ];
        }

        $this->table(
            ['Pair', 'Buy From', 'Buy Price', 'Sell To', 'Sell Price', 'Profit %'],
            $tableData
        );

        $this->line('═══════════════════════════════════════════════════════');
        $this->newLine();
    }
}
