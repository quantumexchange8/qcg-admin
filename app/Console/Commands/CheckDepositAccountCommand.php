<?php

namespace App\Console\Commands;

use App\Models\TradingUser;
use App\Models\Transaction;
use App\Notifications\DepositApprovalNotification;
use App\Services\CTraderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckDepositAccountCommand extends Command
{
    protected $signature = 'check:deposit-account';

    protected $description = 'Check deposit to account error';

    public function handle(): void
    {
        $deposit_errors = Transaction::where('transaction_type', 'deposit')
            ->whereNull('ticket')
            ->where('created_at', '>=', now()->subMinutes(2))
            ->get();

        foreach ($deposit_errors as $transaction) {
            if ($transaction->status == 'successful' && $transaction->transaction_type == 'deposit') {
                $trade = null;
                try {
                    $trade = (new CTraderService)->createTrade($transaction->to_meta_login, $transaction->transaction_amount, "Deposit balance", 'DEPOSIT');
                } catch (\Throwable $e) {
                    Log::error($e->getMessage());
                }
                $ticket = $trade->getTicket();
                $transaction->ticket = $ticket;
                $transaction->save();

                Notification::route('mail', 'payment@currenttech.pro')
                    ->notify(new DepositApprovalNotification($transaction));
            }
        }
    }
}
