<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TradingAccount;
use App\Services\CTraderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Jobs\UpdateCTraderAccountJob;
use App\Services\RunningNumberService;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\GeneralController;
use Illuminate\Validation\ValidationException;

class TradingAccountController extends Controller
{
    public function index()
    {
        return Inertia::render('Member/Account/AccountListing', [
            'accountTypes' => (new GeneralController())->getAccountTypes(true),
        ]);
    }

    public function getAccountListingData(Request $request)
    {
        if ($request->type == 'all') {
            $accountQuery = TradingUser::with([
                'userData:id,first_name,email',
                'trading_account:id,meta_login,equity',
                'accountType:id,name'
            ]);

            if ($request->last_logged_in_days) {
                switch ($request->last_logged_in_days) {
                    case 'greater_than_90_days':
                        $accountQuery->whereDate('last_access', '<=', today()->subDays(90));
                        break;

                    default:
                        $accountQuery->whereDate('last_access', '<=', today());
                }
            }

            $accounts = $accountQuery
                ->orderByDesc('meta_login')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'meta_login' => $account->meta_login,
                        'name' => $account->userData->first_name ?? null,
                        'email' => $account->userData->email ?? null,
                        'balance' => $account->balance,
                        'equity' => $account->trading_account->equity,
                        'credit' => $account->credit,
                        'leverage' => $account->leverage,
                        'last_login' => $account->last_access,
                        'account_type_id' => $account->accountType->id,
                        'account_type' => $account->accountType->name,
                    ];
                });
        } 
        else {
            $accountQuery = TradingUser::onlyTrashed()
                ->with([
                    'userData:id,first_name,email',
                    'trading_account:id,user_id,meta_login',
                    'accountType:id,name'
                ])->withTrashed(['user:id,first_name,email', 'trading_account:id,user_id,meta_login', 'accountType:id,name']);

            $accounts = $accountQuery
                ->orderByDesc('deleted_at')
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'meta_login' => $account->meta_login,
                        'name' => optional($account->userData)->first_name,
                        'email' => optional($account->userData)->email,
                        'balance' => $account->balance,
                        'equity' => 0,
                        'credit' => $account->credit,
                        'leverage' => $account->leverage,
                        'deleted_at' => $account->deleted_at,
                        'last_login' => $account->last_access,
                        'account_type_id' => $account->accountType->id,
                        'account_type' => $account->accountType->name,
                    ];
                });
        }

        return response()->json([
            'accounts' => $accounts
        ]);
    }
        
    public function accountAdjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'remarks' => ['nullable'],
        ])->setAttributeNames([
            'action' => trans('public.action'),
            'amount' => trans('public.amount'),
            'remarks' => trans('public.remarks'),
        ]);
        $validator->validate();

        $cTraderService = (new CTraderService);

        $conn = $cTraderService->connectionStatus();
        if ($conn['code'] != 0) {
            return back()
                ->with('toast', [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]);
        }

        try {
            $cTraderService->getUserInfo($request->meta_login);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return back()
                ->with('toast', [
                    'title' => 'No Account Found',
                    'type' => 'error'
                ]);
        }

        $trading_account = TradingAccount::where('meta_login', $request->meta_login)->first();
        $action = $request->action;
        $type = $request->type;
        $amount = $request->amount;

        if ($type === 'account_balance' && $action === 'balance_out' && ($trading_account->balance - $trading_account->credit) < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        if ($type === 'account_credit' && $action === 'credit_out' && $trading_account->credit < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_credit')]);
        }

        $transaction = Transaction::create([
            'user_id' => $trading_account->user_id,
            'category' => 'trading_account',
            'transaction_type' => $action,
            'from_meta_login' => ($action === 'balance_out' || $action === 'credit_out') ? $trading_account->meta_login : null,
            'to_meta_login' => ($action === 'balance_in' || $action === 'credit_in') ? $trading_account->meta_login : null,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'amount' => $amount,
            'transaction_amount' => $amount,
            'status' => 'processing',
            'remarks' => $request->remarks,
            'handle_by' => Auth::id(),
        ]);

        $changeType = match($type) {
            'account_balance' => match($action) {
                'balance_in' => ChangeTraderBalanceType::DEPOSIT,
                'balance_out' => ChangeTraderBalanceType::WITHDRAW,
                default => throw ValidationException::withMessages(['action' => trans('public.invalid_type')]),
            },
            'account_credit' => match($action) {
                'credit_in' => ChangeTraderBalanceType::DEPOSIT_NONWITHDRAWABLE_BONUS,
                'credit_out' => ChangeTraderBalanceType::WITHDRAW_NONWITHDRAWABLE_BONUS,
                default => throw ValidationException::withMessages(['action' => trans('public.invalid_type')]),
            },
            default => throw ValidationException::withMessages(['action' => trans('public.invalid_type')]),
        };

        try {
            $trade = (new CTraderService)->createTrade($trading_account->meta_login, $amount, $transaction->remarks, $changeType);

            $transaction->update([
                'ticket' => $trade->getTicket(),
                'approved_at' => now(),
                'status' => 'successful',
            ]);

            return redirect()->back()->with('toast', [
                'title' => $type == 'account_balance' ? trans('public.toast_balance_adjustment_success') : trans('public.toast_credit_adjustment_success'),
                'type' => 'success'
            ]);
        } catch (\Throwable $e) {
            // Update transaction status to failed on error
            $transaction->update([
                'approved_at' => now(),
                'status' => 'failed'
            ]);

            // Handle specific error cases
            if ($e->getMessage() == "Not found") {
                TradingUser::firstWhere('meta_login', $trading_account->meta_login)->update(['acc_status' => 'Inactive']);
            } else {
                Log::error($e->getMessage());
            }

            return back()
                ->with('toast', [
                    'title' => 'Adjustment failed',
                    'type' => 'error'
                ]);
        }
    }

    public function accountDelete(Request $request)
    {
        $cTraderService = (new CTraderService);

        $conn = $cTraderService->connectionStatus();
        if ($conn['code'] != 0) {
            return back()
                ->with('toast', [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]);
        }

        try {
            $cTraderService->getUserInfo($request->meta_login);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            return back()
                ->with('toast', [
                    'title' => 'No Account Found',
                    'type' => 'error'
                ]);
        }

        $trading_account = TradingAccount::where('meta_login', $request->meta_login)->first();

        if ($trading_account->balance > 0 || $trading_account->equity > 0 || $trading_account->credit > 0 || $trading_account->cash_equity > 0) {
            return back()
                ->with('toast', [
                    'title' => trans('public.account_have_balance'),
                    'type' => 'error'
                ]);
        }

        try {
            $cTraderService->deleteTrader($trading_account->meta_login);

            $trading_account->trading_user->delete();
            $trading_account->delete();

            // Return success response with a flag for toast
            return redirect()->back()->with('toast', [
                'title' => trans('public.toast_delete_trading_account_success'),
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            // Log the error and return failure response
            Log::error('Failed to delete trading account: ' . $e->getMessage());

            return back()
                ->with('toast', [
                    'title' => 'No Account Found',
                    'type' => 'error'
                ]);
        }
    }

    public function refreshAllAccount(): void
    {
        UpdateCTraderAccountJob::dispatch();
    }
}
