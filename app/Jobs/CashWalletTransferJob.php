<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ChangeTraderBalanceType;
use App\Services\CTraderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CashWalletTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->queue = 'cash_wallet_recovery';
    }

    public function handle(): void
    {
        $usersWithCashAmount = User::whereNot('role', 'super-admin')
            ->where('cash_wallet', '>', 0)
            ->get();

        foreach ($usersWithCashAmount as $user) {
            if (empty($user->ct_user_id)) {
                $ctUser = (new CTraderService)->CreateCTID($user->email);
                $user->ct_user_id = $ctUser['userId'];
                $user->save();

                DB::table('user_action_logs')->insert([
                    'description' => 'Done create CTID: ' . $user->ct_user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $mainPassword = Str::random(8);
            $investorPassword = Str::random(8);
            $accountResponse = (new CTraderService)->createUser($user,  $mainPassword, $investorPassword, 'STANDARD.t', 500, 1, null, null, '');

            if ($accountResponse) {
                $trade = (new CTraderService)->createTrade($accountResponse['login'], number_format($user->cash_wallet, 2), "Refund from Cash Wallet", ChangeTraderBalanceType::DEPOSIT);

                DB::table('user_action_logs')->insert([
                    'description' => 'Complete refunded $ ' . number_format($user->cash_wallet, 2) . ' to USER ID - ' . $user->id . '; LOGIN - ' . $accountResponse['login'] . '; TICKET - ', $trade->getTicket(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Mail::raw('DONE RECOVERY', function ($mail) {
            $mail->to('quantumexchange8@gmail.com')
                ->subject('CASH WALLET REFUND');
        });
    }
}
