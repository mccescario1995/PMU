<?php

namespace App\Console\Commands;

use App\Models\RevenueHistory;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRevenueHistories extends Command
{
    protected $signature = 'revenue:sync {--date= : Recalculate only for this date (Y-m-d)} {--all : Recalculate all dates}';

    protected $description = 'Reconcile revenue_histories against actual transaction totals';

    public function handle(): int
    {
        $specificDate = $this->option('date');
        $all = $this->option('all');

        $query = Transaction::query();

        if ($specificDate) {
            $query->whereDate('transaction_date', $specificDate);
        } elseif (! $all) {
            $this->error('Please specify --date or --all.');

            return 1;
        }

        $transactions = $query
            ->select(DB::raw('DATE(transaction_date) as tx_date'), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('tx_date')
            ->get();

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        foreach ($transactions as $group) {
            RevenueHistory::updateOrCreate(
                ['revenue_date' => $group->tx_date],
                ['total_revenue' => $group->total, 'transaction_count' => $group->count]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$transactions->count()} revenue_history record(s).");

        return 0;
    }
}
