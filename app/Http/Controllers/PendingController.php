<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\TradingUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Models\User;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use App\Services\RunningNumberService;
use Carbon\Carbon;

class PendingController extends Controller
{
    public function withdrawal()
    {
        return Inertia::render('Pending/Withdrawal');
    }

    public function rewards()
    {
        return Inertia::render('Pending/Rewards');
    }

    public function bonus()
    {
        return Inertia::render('Pending/Bonus');
    }

    public function incentive()
    {
        return Inertia::render('Pending/Incentive');
    }

    public function kyc()
    {
        return Inertia::render('Pending/Kyc');
    }

    public function getPendingWithdrawalData()
    {
        $pendingWithdrawals = Transaction::with([
            'user:id,email,first_name,kyc_approval',
            'payment_account:id,payment_account_name,account_no',
            'user.teamHasUser:id,team_id,user_id',
            'user.teamHasUser.team:id,name,color'
        ])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->whereNotIn('category', ['incentive_wallet', 'bonus'])
            ->latest()
            ->get()
            ->map(function ($transaction) {
                // Check if from_meta_login exists and fetch the latest balance
                if ($transaction->from_meta_login) {
                    // Only call getUserInfo in production
                    if (app()->environment('production')) {
                        // Call getUserInfo to ensure the balance is up to date
                        (new CTraderService())->getUserInfo($transaction->from_meta_login); // Pass the from_meta_login object
                    }
                    
                    // After calling getUserInfo, fetch the latest balance
                    $balance = $transaction->from_meta_login->balance ?? 0;
                } else {
                    // Fallback to using the wallet balance if from_meta_login is not available
                    $balance = $transaction->from_wallet->balance ?? 0;
                }
    
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    'user_kyc_status' => $transaction->user->kyc_approval,
                    'from' => $transaction->from_meta_login ? $transaction->from_meta_login : 'rebate_wallet',
                    'balance' => $balance, // Get balance after ensuring it's updated
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'wallet_name' => $transaction->payment_account?->payment_account_name,
                    'wallet_address' => $transaction->payment_account?->account_no,
                    'team_id' => $transaction->user->teamHasUser->team_id ?? null,
                    'team_name' => $transaction->user->teamHasUser->team->name ?? null,
                    'team_color' => $transaction->user->teamHasUser->team->color ?? null,
                ];
            });
    
        $totalAmount = $pendingWithdrawals->sum('amount');
    
        return response()->json([
            'pendingWithdrawals' => $pendingWithdrawals,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function getPendingRewardsData()
    {
        $pendingRewards = Transaction::with([
            'user:id,email,first_name',
            'user.teamHasUser:id,team_id,user_id',
            'user.teamHasUser.team:id,name,color',
            'redemption',
            'redemption.reward:id,type,code,name'
        ])
            ->where('transaction_type', 'redemption')
            ->where('status', 'processing')
            ->where('category', 'trade_points')
            ->latest()
            ->get()
            ->map(function ($transaction) {
                    // Fallback to using the wallet balance if from_meta_login is not available
                $balance = $transaction->from_wallet->balance ?? 0;
                $reward_name = json_decode($transaction->redemption->reward->name, true);
    
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    'balance' => $balance, // Get balance after ensuring it's updated
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'team_id' => $transaction->user->teamHasUser->team_id ?? null,
                    'team_name' => $transaction->user->teamHasUser->team->name ?? null,
                    'team_color' => $transaction->user->teamHasUser->team->color ?? null,
                    'reward_id' => $transaction->redemption->reward_id ?? null,
                    'reward_details' => $transaction->redemption ?? null,
                    'reward_type' => $transaction->redemption->reward->type ?? null,
                    'reward_code' => $transaction->redemption->reward->code ?? null,
                    'reward_name' => $reward_name ?? null,
                ];
            });
    
        $totalAmount = $pendingRewards->sum('amount');
    
        return response()->json([
            'pendingRewards' => $pendingRewards,
            'totalAmount' => $totalAmount,
        ]);
    }
            
    public function getPendingBonusData()
    {
        $pendingBonus = Transaction::with([
            'user:id,email,first_name',
            'payment_account:id,payment_account_name,account_no',
            'user.teamHasUser:id,team_id,user_id',
            'user.teamHasUser.team:id,name,color'
        ])
            ->whereIn('transaction_type', ['credit_bonus'])
            ->where('status', 'processing')
            ->where('category', 'bonus')
            ->latest()
            ->get()
            ->map(function ($transaction) {
                // Check if to_meta_login exists and fetch the latest balance
                if ($transaction->to_meta_login) {
                    // Only call getUserInfo in production
                    if (app()->environment('production')) {
                        // Call getUserInfo to ensure the balance is up to date
                        (new CTraderService())->getUserInfo($transaction->to_meta_login); // Pass the to_meta_login object
                    }
                    
                    // After calling getUserInfo, fetch the latest balance
                    $balance = $transaction->to_meta_login->balance ?? 0;
                } else {
                    // Fallback to using the wallet balance if to_meta_login is not available
                    $balance = $transaction->from_wallet->balance ?? 0;
                }
                
                $previousCreditBonus = Transaction::where('to_meta_login', $transaction->to_meta_login)
                    ->where('transaction_type', 'credit_bonus')
                    ->where('created_at', '<', $transaction->created_at)
                    ->whereNot('status', 'rejected')
                    ->latest('created_at')
                    ->first();

                // Define the query
                $depositQuery = Transaction::where('to_meta_login', $transaction->to_meta_login)
                    ->whereIn('transaction_type', ['deposit', 'balance_in'])
                    ->where('created_at', '<', $transaction->created_at);

                // Modify query if previous credit_bonus exists
                if ($previousCreditBonus) {
                    $depositQuery->where('created_at', '>', $previousCreditBonus->created_at);
                }

                $deposit_amount = $depositQuery->sum('transaction_amount');

                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    'from' => $transaction->to_meta_login ? $transaction->to_meta_login : 'bonus',
                    'balance' => $balance, // Get balance after ensuring it's updated
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'wallet_name' => $transaction->payment_account?->payment_account_name,
                    'wallet_address' => $transaction->payment_account?->account_no,
                    'team_id' => $transaction->user->teamHasUser->team_id ?? null,
                    'team_name' => $transaction->user->teamHasUser->team->name ?? null,
                    'team_color' => $transaction->user->teamHasUser->team->color ?? null,
                    // 'deposit_date' => $lastDeposit->approved_at ?? null,
                    'deposit_amount' => $deposit_amount ?? 0,
                ];
            });
    
        $totalAmount = $pendingBonus->sum('amount');
    
        return response()->json([
            'pendingBonus' => $pendingBonus,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function withdrawalApproval(Request $request)
    {
        $type = $request->type;
        $action = $request->action;
        $status = $action == 'approve' ? 'successful' : 'rejected';
    
        $transactionIds = $request->selectedIds ? $request->selectedIds : [$request->id];
    
        // Initialize message variable
        $message = '';
    
        foreach ($transactionIds as $transactionId) {
            $transaction = Transaction::find($transactionId);
    
            if ($transaction && $transaction->status == 'processing') {
                $transaction->update([
                    'remarks' => $request->remarks,
                    'status' => $status,
                    'approved_at' => now(),
                    'handle_by' => Auth::id()
                ]);
    
                if ($transaction->status == 'rejected') {
                    if ($transaction->from_wallet_id) {
                        // Handle wallet update logic
                        $wallet = Wallet::where('id', $transaction->from_wallet_id)->first();
                        if ($wallet) {
                            $old_balance = $wallet->balance;
                            $new_balance = $old_balance + $transaction->amount;
                            $wallet->update(['balance' => $new_balance]);
                            $transaction->update([
                                'old_wallet_amount' => $old_balance,
                                'new_wallet_amount' => $new_balance,
                            ]);
                        }
                    } elseif ($transaction->from_meta_login) {
                        // Handle trading account logic
                        try {
                            $trade = (new CTraderService)->createTrade(
                                $transaction->from_meta_login,
                                $transaction->amount,
                                $transaction->remarks,
                                ChangeTraderBalanceType::DEPOSIT
                            );
    
                            $transaction->update(['ticket' => $trade->getTicket()]);
                        } catch (\Throwable $e) {
                            if ($e->getMessage() == "Not found") {
                                TradingUser::firstWhere('meta_login', $transaction->from_meta_login)
                                    ->update(['acc_status' => 'inactive']);
                            } else {
                                Log::error($e->getMessage());
                            }
    
                            return redirect()->back()->with('toast', [
                                'title' => 'Trading account error occurred.',
                                'type' => 'error'
                            ]);
                        }
                    } elseif ($transaction->category == 'bonus') {
                        $tradingAccount = TradingAccount::where('meta_login', $transaction->to_meta_login)->first();

                        if ($tradingAccount) {
                            $tradingAccount->update([
                                'is_claimed' => 'claimable',
                            ]);
                        }
                    }

    
                    $messages = [
                        'withdrawal' => trans('public.toast_reject_withdrawal_request_success'),
                        'bonus' => trans('public.toast_reject_bonus_request_success'),
                        'incentive' => trans('public.toast_reject_incentive_request_success'),
                        'rewards' => trans('public.toast_reject_rewards_request_success'),
                    ];
                    $message = $messages[$type];
                } else {
                    // Check if the category is 'bonus' and handle accordingly
                    if ($transaction->category == 'bonus') {
                        $tradingAccount = TradingAccount::where('meta_login', $transaction->to_meta_login)->first();

                        if ($tradingAccount) {
                            $tradingAccount->decrement('claimable_amount', $transaction->transaction_amount);
                            
                            $tradeCredit = (new CTraderService)->createTrade($tradingAccount->meta_login, $transaction->amount, "Promotion Account Bonus", ChangeTraderBalanceType::DEPOSIT_NONWITHDRAWABLE_BONUS);
                            $ticketCredit = $tradeCredit->getTicket();
                            $transaction->update(['ticket' => $ticketCredit]);
                            if ($tradingAccount->achieved_amount == $tradingAccount->target_amount || $tradingAccount->applicable_deposit == 'first_deposit_only') {
                                $tradingAccount->update([
                                    'is_claimed' => 'completed',
                                ]);
                            }
                            else {
                                $tradingAccount->update([
                                    'is_claimed' => 'claimable',
                                ]);
                            }
                        }
                    } elseif ($transaction->category == 'trade_points' || $transaction->transaction_type == 'redemption') {
                        if ($transaction->redemption->reward->type = 'cash_rewards') {
                            $tradingAccount = TradingAccount::where('meta_login', $transaction->redemption->receiving_account)->first();

                            if ($tradingAccount) { 
                                $tradeCash = (new CTraderService)->createTrade($tradingAccount->meta_login, $transaction->amount, "Cash Reward", ChangeTraderBalanceType::DEPOSIT);
                                $ticketCash = $tradeCash->getTicket();

                                Transaction::create([
                                    'user_id' => $tradingAccount->user_id,
                                    'category' => 'trading_account',
                                    'transaction_type' => 'cash_reward',
                                    'to_meta_login' => $transaction->redemption->receiving_account,
                                    'ticket' => $ticketCash,
                                    'transaction_number' => RunningNumberService::getID('transaction'),
                                    'amount' => $transaction->redemption->reward->cash_amount,
                                    'transaction_amount' => $transaction->redemption->reward->cash_amount,
                                    'status' => 'successful',
                                    'remarks' => 'Cash Reward Approved',
                                    'handle_by' => Auth::id(),
                                ]);
                            }
                        }
                    }

                    $messages = [
                        'withdrawal' => trans('public.toast_approve_withdrawal_request_success'),
                        'bonus' => trans('public.toast_approve_bonus_request_success'),
                        'incentive' => trans('public.toast_approve_incentive_request_success'),
                        'rewards' => trans('public.toast_approve_rewards_request_success'),
                    ];
                    $message = $messages[$type];

                }
            } else {
                return redirect()->back()->with('toast', [
                    'title' => 'Invalid action or transaction not found.',
                    'type' => 'warning'
                ]);
            }
        }
    
        // Return success message if no error occurred
        return redirect()->back()->with('toast', [
            'title' => $message,
            'type' => 'success'
        ]);
    }
            
    public function getPendingIncentiveData()
    {
        $pendingIncentives = Transaction::with([
            'user:id,email,first_name',
            'payment_account:id,payment_account_name,account_no',
            'user.teamHasUser:id,team_id,user_id',
            'user.teamHasUser.team:id,name,color'
        ])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->where('category', 'incentive_wallet')
            ->latest()
            ->get()
            ->map(function ($transaction) {
                // Check if from_meta_login exists and fetch the latest balance
                if ($transaction->from_meta_login) {
                    // Only call getUserInfo in production
                    if (app()->environment('production')) {
                        // Call getUserInfo to ensure the balance is up to date
                        (new CTraderService())->getUserInfo($transaction->from_meta_login); // Pass the from_meta_login object
                    }
    
                    // After the getUserInfo call, we can safely fetch the latest balance
                    $balance = $transaction->from_meta_login->balance ?? 0;
                } else {
                    // Fallback to using the wallet balance if from_meta_login is not available
                    $balance = $transaction->from_wallet->balance ?? 0;
                }
    
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    'from' => $transaction->from_meta_login ? $transaction->from_meta_login : 'incentive',
                    'balance' => $balance,
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'wallet_name' => $transaction->payment_account?->payment_account_name,
                    'wallet_address' => $transaction->payment_account?->account_no,
                    'team_id' => $transaction->user->teamHasUser->team_id ?? null,
                    'team_name' => $transaction->user->teamHasUser->team->name ?? null,
                    'team_color' => $transaction->user->teamHasUser->team->color ?? null,
                ];
            });
    
        $totalAmount = $pendingIncentives->sum('amount');
    
        return response()->json([
            'pendingIncentives' => $pendingIncentives,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function getPendingKycData()
    {
        $pendingKycs = User::with(['media'])
        ->where('kyc_approval', 'pending')
        ->get()
        ->map(function ($user) {
            $media = $user->getMedia('kyc_verification');
            $submittedAt = $media->min('created_at'); // Use min() if there are 2 files

            return [
                'id' => $user->id,
                'name' => $user->chinese_name ?? $user->first_name,
                'email' => $user->email,
                'submitted_at' => $submittedAt,
                'kyc_files' => $media,
            ];
        })
        ->sortByDesc('submitted_at') // sort the final result
        ->values(); // reset index
    
        return response()->json([
            'pendingKycs' => $pendingKycs,
        ]);
    }

    public function kycApproval(Request $request)
    {
        $action = $request->action;
        $status = $action == 'approve' ? 'verified' : 'unverified';
        $user_id = $request->user_id;

        $rules = [
            'remarks' => $request->action === 'reject' ? 'required' : 'nullable', // Only require if 'reject'
        ];
        
        $validator = Validator::make($request->all(), $rules);
        
        $validator->setAttributeNames([
            'remarks' => trans('public.remarks'),
        ]);
        
        $validator->validate();
    
        $user = User::find($user_id);
        $user->update([
            'kyc_approval' => $status,
            'kyc_approval_description' => $request->remarks ?? null ,
            'kyc_approved_at' => now(),
        ]);

        $messages = [
            'verified' => trans('public.toast_approve_kyc_verification_success'),
            'unverified' => trans('public.toast_reject_kyc_verification_success'),
        ];
        $message = $messages[$status];
    
        // Return success message if no error occurred
        return redirect()->back()->with('toast', [
            'title' => $message,
            'type' => 'success'
        ]);
    }
}
