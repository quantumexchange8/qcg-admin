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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AccountListingExport;
use App\Jobs\UpdateCTraderAccountJob;
use App\Services\RunningNumberService;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\GeneralController;
use Illuminate\Validation\ValidationException;
use App\Services\Data\UpdateTradingUser;
use App\Services\Data\UpdateTradingAccount;

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

            $inactiveThreshold = now()->subDays(90)->startOfDay();

            // Step 1: Get the latest transaction for each meta_login using ROW_NUMBER()
            $subQuery = DB::table(function ($query) use ($inactiveThreshold) {
                $query->select('meta_login', 'transaction_type', 'created_at')
                    ->from(function ($subQuery) use ($inactiveThreshold) {
                        // Get the transactions where either from_meta_login or to_meta_login matches the meta_login
                        $subQuery->select('from_meta_login as meta_login', 'transaction_type', 'created_at')
                            ->from('transactions')
                            ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                            ->where('created_at', '>=', $inactiveThreshold)
                            ->unionAll(
                                DB::table('transactions')
                                    ->select('to_meta_login as meta_login', 'transaction_type', 'created_at')
                                    ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                                    ->where('created_at', '>=', $inactiveThreshold)
                            );
                    }, 'meta_logins')
                    ->addSelect(DB::raw('ROW_NUMBER() OVER (PARTITION BY meta_login ORDER BY created_at DESC) as row_num'));
            }, 'latest_transactions')
            ->where('row_num', 1)  // Only take the latest row
            ->select('meta_login', 'transaction_type', 'created_at');

            // Step 2: Join with trading_users to get the complete account data
            $accounts = TradingUser::with([
                    'userData:id,first_name,email',
                    'trading_account:id,meta_login,equity,status',
                    'accountType:id,name',
                ])
                ->leftJoinSub(
                    $subQuery, // Join the subquery
                    'latest_transactions',
                    function ($join) {
                        $join->on('trading_users.meta_login', '=', 'latest_transactions.meta_login');
                    }
                )
                ->select('trading_users.*', 'latest_transactions.created_at as last_transaction_at')
                ->orderByDesc('meta_login')
                ->get()
                ->map(function ($account) use ($inactiveThreshold): array {
                    // Determine if the account is active based on transaction activity
                    $isActive = $account->created_at >= $inactiveThreshold ||
                                ($account->last_transaction_at && $account->last_transaction_at >= $inactiveThreshold);

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
                        'is_active' => $isActive,
                        'status' => $account->trading_account->status,
                    ];
                }
            );
        } else {
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

    public function getAccountListingPaginate(Request $request)
    {
        if ($request->has('lazyEvent')) {
            $data = json_decode($request->only(['lazyEvent'])['lazyEvent'], true);

            $type = $request->type;

            if ($type === 'all') {
                $inactiveThreshold = now()->subDays(90)->startOfDay();

                $query = TradingUser::query()
                    ->with(['userData:id,first_name,email', 'trading_account:id,meta_login,equity,status', 'accountType']) // Eager load related models
                    ->where('acc_status', 'active');

                // Filters
                if ($data['filters']['global']['value']) {
                    $keyword = $data['filters']['global']['value'];
                    $query->where(function ($query) use ($keyword) {
                        $query->whereHas('userData', function ($query) use ($keyword) {
                            $query->where('first_name', 'like', '%' . $keyword . '%')
                                  ->orWhere('email', 'like', '%' . $keyword . '%');
                        })
                        ->orWhere('meta_login', 'like', '%' . $keyword . '%');
                    });
                }

                if ($data['filters']['account_type_id']['value']) {
                    $query->where('account_type_id', $data['filters']['account_type_id']['value']);
                }

                if (isset($data['sortField']) && isset($data['sortOrder'])) {
                    $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                    $query->orderBy($data['sortField'], $order);
                } else {
                    $query->orderByDesc('meta_login'); // Default sorting
                }

                // Export logic
                if ($request->has(key: 'exportStatus') && $request->exportStatus == true) {
                    $accounts = $query->clone();
                    return Excel::download(new AccountListingExport($accounts), now() . '-accounts.xlsx');
                }

                // Fetch the accounts with selected fields and pagination
                $accounts = $query
                    ->select([
                        'id',
                        'user_id',
                        'meta_login',
                        'account_type',
                    ])
                    ->where('acc_status', 'active')
                    ->paginate($data['rows']);

                // Iterate over each account on the current page and update the account status
                foreach ($accounts->items() as $account) {
                    try {
                        // Attempt to fetch user data
                        $accData = (new CTraderService())->getUser($account->meta_login);

                        // If no data is returned (null or empty), mark the account as inactive
                        if (empty($accData)) {
                            if ($account->acc_status !== 'inactive') {
                                $account->update(['acc_status' => 'inactive']);
                            }
                        } else {
                            // Proceed with updating account information
                            (new UpdateTradingUser)->execute($account->meta_login, $accData);
                            (new UpdateTradingAccount)->execute($account->meta_login, $accData);
                        }
                    } catch (\Exception $e) {
                        // Log the error if there was a failure (network issue, server error, etc.)
                        Log::error("Error fetching data for account {$account->meta_login}: {$e->getMessage()}");
                    }
                }

                // Now re-fetch the data after updating the statuses
                $accounts = $query
                    ->select([
                        'id',
                        'user_id',
                        'meta_login',
                        'account_type',
                        'balance',
                        'credit',
                        'leverage',
                        'last_access as last_login',
                        'created_at',
                    ])
                    ->where('acc_status', 'active')
                    ->paginate($data['rows']);

                // After the accounts are retrieved, you can access `getFirstMediaUrl` for each user using foreach
                foreach ($accounts as $account) {
                    $account->name = $account->userData->first_name;
                    $account->email = $account->userData->email;
                    $account->equity = $account->trading_account->equity;
                    $account->account_type_id = $account->accountType->id;
                    $account->account_type = $account->accountType->name;
                    // Calculate `is_active` dynamically
                    $account->is_active = (
                        ($account->last_login >= $inactiveThreshold) ||
                        ($account->created_at >= $inactiveThreshold) ||
                        ($account->balance > 0) ||
                        ($account->credit > 0) ||
                        ($account->equity > 0)
                    );

                    $account->status = $account->trading_account->status;

                    // Remove unnecessary nested data (users and trading_account)
                    unset($account->userData);
                    unset($account->trading_account);
                    unset($account->accountType);
                }

                // After the status update, return the re-fetched paginated data
                return response()->json([
                    'success' => true,
                    'data' => $accounts,
                ]);
            } else {
                // Handle inactive accounts or other types
                $query = TradingUser::onlyTrashed()
                    ->withTrashed(['userData:id,first_name,email', 'trading_account:id,meta_login,equity,status', 'accountType']); // Eager load related models

                    // Filters
                    if ($data['filters']['global']['value']) {
                        $keyword = $data['filters']['global']['value'];
                        $query->where(function ($query) use ($keyword) {
                            $query->whereHas('userData', function ($query) use ($keyword) {
                                $query->where('first_name', 'like', '%' . $keyword . '%')
                                      ->orWhere('email', 'like', '%' . $keyword . '%');
                            })
                            ->orWhere('meta_login', 'like', '%' . $keyword . '%');
                        });
                    }

                    if ($data['filters']['account_type_id']['value']) {
                        $query->where('account_type_id', $data['filters']['account_type_id']['value']);
                    }

                    if (isset($data['sortField']) && isset($data['sortOrder'])) {
                        $order = $data['sortOrder'] == 1 ? 'asc' : 'desc';
                        $query->orderBy($data['sortField'], $order);
                    } else {
                        $query->orderByDesc('meta_login'); // Default sorting
                    }

                    // Export logic
                    if ($request->has('exportStatus') && $request->exportStatus == true) {
                        $accounts = $query;
                        return Excel::download(new AccountListingExport($accounts), now() . '-accounts.xlsx');
                    }

                $accounts = $query
                    ->select([
                        'id',
                        'user_id',
                        'meta_login',
                        'account_type',
                        'balance',
                        DB::raw('0 as equity'), // Inactive accounts have no equity
                        'credit',
                        'leverage',
                        'deleted_at',
                        'last_access as last_login',
                    ])
                    ->paginate($data['rows']);

                // After the accounts are retrieved, you can access `getFirstMediaUrl` for each user using foreach
                foreach ($accounts as $account) {
                    $account->name = $account->userData->first_name;
                    $account->email = $account->userData->email;
                    $account->account_type_id = $account->accountType->id;
                    $account->account_type = $account->accountType->name;

                    // Remove unnecessary nested data (users and trading_account)
                    unset($account->userData);
                    unset($account->trading_account);
                    unset($account->accountType);
                }

                return response()->json([
                    'success' => true,
                    'data' => $accounts,
                ]);
            }
        }

        return response()->json(['success' => false, 'data' => []]);
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
                TradingUser::firstWhere('meta_login', $trading_account->meta_login)->update(['acc_status' => 'inactive']);
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

    public function updateAccountStatus(Request $request)
    {
        $account = TradingAccount::where('meta_login', $request->meta_login)->first();

        // If the account is inactive, immediately activate it and return
        if ($account->status === 'inactive') {
            $account->status = 'active';
            $account->save();

            return back()->with('toast', [
                'title' => trans('public.toast_trading_account_has_activated'),
                'type' => 'success',
            ]);
        } elseif ($account->status === 'active') {
            $inactiveThreshold = now()->subDays(90);

            // Check if the account has any positive balances
            $hasPositiveBalance = $account->balance > 0 || $account->equity > 0 || $account->credit > 0 || $account->cash_equity > 0;

            // Check if the account was created recently (within the last 90 days)
            $isRecentlyCreated = $account->created_at->diffInDays(now()) <= 90;

            // Return warning if recently created or has a positive balance
            if ($isRecentlyCreated) {
                return back()->with('toast', [
                    'title' => trans('public.toast_trading_account_created_recently'),
                    'type' => 'warning',
                ]);
            }

            if ($hasPositiveBalance) {
                return back()->with('toast', [
                    'title' => trans('public.toast_trading_account_has_balance'),
                    'type' => 'warning',
                ]);
            }

            // Check for recent transactions
            $lastTransaction = $account->transactions()
                                    ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                                    ->where('created_at', '>=', $inactiveThreshold)
                                    ->latest()
                                    ->first();

            // Get the last access date of the trading user
            $lastAccess = $account->trading_user->last_access;

            if (($lastTransaction && $lastTransaction->created_at >= $inactiveThreshold) ||
                ($lastAccess && $lastAccess >= $inactiveThreshold && $lastAccess <= now())) {
                // Recent activity -> cannot deactivate
                return back()->with('toast', [
                    'title' => trans('public.toast_trading_account_has_recent_activity'),
                    'type' => 'warning',
                ]);
            }

            // No recent activity -> deactivate account
            $account->status = 'inactive';
            $account->save();

            return back()->with('toast', [
                'title' => trans('public.toast_trading_account_has_deactivated'),
                'type' => 'success',
            ]);
        }
    }

    public function refreshAllAccount(): void
    {
        UpdateCTraderAccountJob::dispatch();
    }
}
