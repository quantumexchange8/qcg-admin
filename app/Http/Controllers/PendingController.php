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

    public function incentive()
    {
        return Inertia::render('Pending/Incentive');
    }

    public function getPendingWithdrawalData()
    {
        $pendingWithdrawals = Transaction::with([
            'user:id,email,first_name',
            'payment_account:id,payment_account_name,account_no',
        ])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->whereNot('category', 'incentive_wallet')
            ->latest()
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    // 'user_profile_photo' => $transaction->user->getFirstMediaUrl('profile_photo'),
                    'from' => $transaction->from_meta_login ? $transaction->from_meta_login : 'rebate_wallet',
                    'balance' => $transaction->from_meta_login ? $transaction->from_meta_login->balance ?? 0 : $transaction->from_wallet->balance ?? 0,
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'wallet_name' => $transaction->payment_account?->payment_account_name,
                    'wallet_address' => $transaction->payment_account?->account_no,
                ];
            });

        $totalAmount = $pendingWithdrawals->sum('amount');

        return response()->json([
            'pendingWithdrawals' => $pendingWithdrawals,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function withdrawalApproval(Request $request)
    {
        $type = $request->type;

        $action = $request->action;
    
        $status = $action == 'approve' ? 'successful' : 'rejected';
    
        $transaction = Transaction::find($request->id);
    
        if ($transaction->status != 'processing') {
            return redirect()->back()->with('toast', [
                'title' => 'Invalid action. Please try again.',
                'type' => 'warning'
            ]);
        }
    
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
                            ->update(['acc_status' => 'Inactive']);
                    } else {
                        Log::error($e->getMessage());
                    }
        
                    return back()->with('toast', [
                        'title' => 'Trading account error',
                        'type' => 'error'
                    ]);
                }
            }
        
            return redirect()->back()->with('toast', [
                'title' => $type === 'withdrawal'
                    ? trans('public.toast_reject_withdrawal_request_success')
                    : trans('public.toast_reject_incentive_request_success'),
                'type' => 'success'
            ]);
        } else {
            return redirect()->back()->with('toast', [
                'title' => $type === 'withdrawal'
                    ? trans('public.toast_approve_withdrawal_request_success')
                    : trans('public.toast_approve_incentive_request_success'),
                'type' => 'success'
            ]);
        }
    }
    
    public function getPendingIncentiveData()
    {
        $pendingincentives = Transaction::with([
            'user:id,email,first_name',
            'payment_account:id,payment_account_name,account_no',
        ])
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'processing')
            ->where('category', 'incentive_wallet')
            ->latest()
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'user_name' => $transaction->user->first_name,
                    'user_email' => $transaction->user->email,
                    // 'user_profile_photo' => $transaction->user->getFirstMediaUrl('profile_photo'),
                    'from' => $transaction->from_meta_login ? $transaction->from_meta_login : 'incentive',
                    'balance' => $transaction->from_meta_login ? $transaction->from_meta_login->balance ?? 0 : $transaction->from_wallet->balance ?? 0,
                    'amount' => $transaction->amount,
                    'transaction_charges' => $transaction->transaction_charges,
                    'transaction_amount' => $transaction->transaction_amount,
                    'wallet_name' => $transaction->payment_account?->payment_account_name,
                    'wallet_address' => $transaction->payment_account?->account_no,
                ];
            });

        $totalAmount = $pendingincentives->sum('amount');

        return response()->json([
            'pendingincentives' => $pendingincentives,
            'totalAmount' => $totalAmount,
        ]);
    }
}
