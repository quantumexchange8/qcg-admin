<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\TradingUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\CTraderService;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PendingController extends Controller
{
    public function withdrawal()
    {
        return Inertia::render('Pending/Withdrawal');
    }

    public function bonus()
    {
        return Inertia::render('Pending/Bonus');
    }

    public function incentive()
    {
        return Inertia::render('Pending/Incentive');
    }

    public function getPendingWithdrawalData()
    {
        $pendingWithdrawals = Transaction::with([
            'user:id,email,first_name',
            'payment_account:id,payment_account_name,account_no',
            'user.teamHasUser:id,team_id,user_id',
            'user.teamHasUser.team:id,name,color'
        ])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->whereNotIn('category', ['incentive_wallet', 'bonus_wallet'])
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
            
    public function getPendingBonusData()
    {
        $pendingBonus = Transaction::with([
            'user:id,email,first_name',
            'payment_account:id,payment_account_name,account_no',
            'user.teamHasUser:id,team_id,user_id',
            'user.teamHasUser.team:id,name,color'
        ])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->where('category', 'bonus_wallet')
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
                
                // Fetch the last deposit before the current transaction date
                $lastDeposit = Transaction::where('user_id', $transaction->user_id)
                    ->where('transaction_type', 'deposit')
                    ->where('status', 'successful')
                    ->where('created_at', '<', $transaction->created_at) // Before the current transaction date
                    ->orderBy('created_at', 'desc') // Order by latest
                    ->first();

                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    'from' => $transaction->from_meta_login ? $transaction->from_meta_login : 'bonus_wallet',
                    'balance' => $balance, // Get balance after ensuring it's updated
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'wallet_name' => $transaction->payment_account?->payment_account_name,
                    'wallet_address' => $transaction->payment_account?->account_no,
                    'team_id' => $transaction->user->teamHasUser->team_id ?? null,
                    'team_name' => $transaction->user->teamHasUser->team->name ?? null,
                    'team_color' => $transaction->user->teamHasUser->team->color ?? null,
                    'deposit_date' => $lastDeposit->approved_at ?? null,
                    'deposit_amount' => $$lastDeposit ? $lastDeposit->transaction_amount : 0,
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
                    }
    
                    $messages = [
                        'withdrawal' => trans('public.toast_reject_withdrawal_request_success'),
                        'bonus' => trans('public.toast_reject_bonus_request_success'),
                        'incentive' => trans('public.toast_reject_incentive_request_success'),
                    ];
                    $message = $messages[$type];
                } else {
                    $messages = [
                        'withdrawal' => trans('public.toast_approve_withdrawal_request_success'),
                        'bonus' => trans('public.toast_approve_bonus_request_success'),
                        'incentive' => trans('public.toast_approve_incentive_request_success'),
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
}
