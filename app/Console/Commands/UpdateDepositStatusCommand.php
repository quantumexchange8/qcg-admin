<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class UpdateDepositStatusCommand extends Command
{
    protected $signature = 'update:deposit-status';

    protected $description = 'Update deposit status of transaction';

    public function handle(): void
    {
        $transactions = Transaction::where([
            'transaction_type' => 'deposit',
            'status' => 'processing',
        ])
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->update([
                'status' => 'failed',
                'approved_at' => now(),
                'remarks' => 'System Approval',
            ]);
        }
    }
}
