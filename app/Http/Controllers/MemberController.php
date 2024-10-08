<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use App\Models\TradingAccount;
use Illuminate\Validation\Rule;
use App\Models\RebateAllocation;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\RunningNumberService;
use App\Http\Requests\AddMemberRequest;
use App\Services\DropdownOptionService;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\GeneralController;

class MemberController extends Controller
{
    public function listing()
    {
        $totalMembers = User::where('role', 'member')->count();
        $totalAgents = User::where('role', 'agent')->count();
    
        return Inertia::render('Member/Listing/MemberListing', [
            'totalMembers' => $totalMembers,
            'totalAgents' => $totalAgents,
            'countries' => (new GeneralController())->getCountries(true),
            'uplines' => (new GeneralController())->getUplines(true),
            'teams' => (new GeneralController())->getTeams(true),
        ]);
    }

    public function getMemberListingData(Request $request)
    {
        $role = $request->role;
    
        $query = User::with(['teamHasUser'])
            ->whereNot('role', 'super-admin')
            ->when($role, function ($query, $role) {
                return $query->where('role', $role);
            })
            ->latest()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name,
                    'email' => $user->email,
                    'upline_id' => $user->upline_id,
                    'role' => $user->role,
                    'id_number' => $user->id_number,
                    'team_id' => $user->teamHasUser->team_id ?? null,
                    'team_name' => $user->teamHasUser->team->name ?? null,
                    'team_color' => $user->teamHasUser->team->color ?? null,
                    'status' => $user->status,
                ];
            });
    
        // Count the number of users where role is 'member' and 'agent'
        $memberCount = User::where('role', 'member')->count();
        $agentCount = User::where('role', 'agent')->count();
    
        return response()->json([
            'users' => $query,
            'total_members' => $memberCount,
            'total_agents' => $agentCount,
        ]);
    }
    
    public function addNewMember(Request $request)
    {
        $upline_id = $request->upline['value'];
        $upline = User::find($upline_id);

        if(empty($upline->hierarchyList)) {
            $hierarchyList = "-" . $upline_id . "-";
        } else {
            $hierarchyList = $upline->hierarchyList . $upline_id . "-";
        }

        $dial_code = $request->dial_code;
        $country = Country::find($dial_code['id']);
        $default_agent_id = User::where('id_number', 'AID00000')->first()->id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'country' => $request->country,
            'dial_code' => $dial_code['phone_code'],
            'phone' => $request->phone,
            'phone_number' => $request->phone_number,
            'upline_id' => $upline_id,
            'country_id' => $country->id,
            'nationality' => $country->nationality,
            'hierarchyList' => $hierarchyList,
            'password' => Hash::make($request->password),
            'role' => $upline_id == $default_agent_id ? 'agent' : 'member',
            'kyc_approval' => 'verified',
        ]);

        $user->setReferralId();

        $id_no = ($user->role == 'agent' ? 'AID' : 'MID') . Str::padLeft($user->id - 2, 5, "0");
        $user->id_number = $id_no;
        $user->save();

        if ($upline->teamHasUser) {
            $user->assignedTeam($upline->teamHasUser->group_id);
        }

        if ($user->role == 'agent') {
            Wallet::create([
                'user_id' => $user->id,
                'type' => 'rebate_wallet',
                'address' => str_replace('AID', 'RB', $user->id_number),
                'balance' => 0
            ]);

            $uplineRebates = RebateAllocation::where('user_id', $user->upline_id)->get();

            foreach ($uplineRebates as $uplineRebate) {
                RebateAllocation::create([
                    'user_id' => $user->id,
                    'account_type_id' => $uplineRebate->account_type_id,
                    'symbol_group_id' => $uplineRebate->symbol_group_id,
                    'amount' => 0,
                    'edited_by' => Auth::id(),
                ]);
            }
        }

        return back()->with('toast', [
            'title' => trans("public.toast_create_member_success"),
            'type' => 'success',
        ]);
    }

    public function updateMemberStatus(Request $request)
    {
        $user = User::find($request->id);

        $user->status = $user->status == 'active' ? 'inactive' : 'active';
        $user->save();

        return back()->with('toast', [
            'title' => $user->status == 'active' ? trans("public.toast_member_has_activated") : trans("public.toast_member_has_deactivated"),
            'type' => 'success',
        ]);
    }


    public function getAvailableUplineData(Request $request)
    {
        $user = User::with('upline')->find($request->user_id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $availableUpline = $user->upline;

        while ($availableUpline && $availableUpline->role != 'agent') {
            $availableUpline = $availableUpline->upline;
        }

        // $availableUpline->profile_photo = $availableUpline->getFirstMediaUrl('profile_photo');

        $uplineRebate = RebateAllocation::with('symbol_group:id,display')
            ->where('user_id', $availableUpline->id);

        $availableAccountTypeId = $uplineRebate->get()->pluck('account_type_id')->toArray();

        $accountTypeSel = AccountType::whereIn('id', $availableAccountTypeId)
            ->select('id', 'name')
            ->get()
            ->map(function ($accountType) {
                return [
                    'id' => $accountType->id,
                    'name' => $accountType->name,
                ];
            });

        return response()->json([
            'availableUpline' => $availableUpline,
            'rebateDetails' => $uplineRebate->get(),
            'accountTypeSel' => $accountTypeSel,
        ]);
    }

    public function upgradeAgent(Request $request)
    {
        $user_id = $request->user_id;
        $amounts = $request->amounts;

        // Find the upline user and their rebate allocations
        $user = User::find($user_id);
        $upline_user = $user->upline;
        $uplineRebates = RebateAllocation::where('user_id', $upline_user->id)->get();

        // Get the account_type_id and symbol_group_id combinations for the upline
        $uplineCombinations = $uplineRebates->map(function($rebate) {
            return [
                'account_type_id' => $rebate->account_type_id,
                'symbol_group_id' => $rebate->symbol_group_id
            ];
        })->toArray();

        // Get the account_type_id and symbol_group_id combinations from the request
        $requestCombinations = array_map(function($amount) {
            return [
                'account_type_id' => $amount['account_type_id'],
                'symbol_group_id' => $amount['symbol_group_id']
            ];
        }, $amounts);

        $errors = [];

        // Validate amounts
        foreach ($amounts as $index => $amount) {
            $uplineRebate = RebateAllocation::find($amount['rebate_detail_id']);

            if ($uplineRebate && $amount['amount'] > $uplineRebate->amount) {
                $errors["amounts.$index"] = 'Amount should not be higher than $' . $uplineRebate->amount;
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Create rebate allocations for amounts in the request
        foreach ($amounts as $amount) {
            RebateAllocation::create([
                'user_id' => $user_id,
                'account_type_id' => $amount['account_type_id'],
                'amount' => $amount['amount'],
                'symbol_group_id' => $amount['symbol_group_id'],
                'edited_by' => Auth::id()
            ]);
        }

        // Create entries for missing combinations with amount 0
        foreach ($uplineCombinations as $combination) {
            if (!in_array($combination, $requestCombinations)) {
                RebateAllocation::create([
                    'user_id' => $user_id,
                    'account_type_id' => $combination['account_type_id'],
                    'amount' => 0,
                    'symbol_group_id' => $combination['symbol_group_id'],
                    'edited_by' => Auth::id()
                ]);
            }
        }

        $user->id_number = $request->id_number;
        $user->role = 'agent';
        $user->save();

        Wallet::create([
            'user_id' => $user->id,
            'type' => 'rebate_wallet',
            'address' => str_replace('AID', 'RB', $user->id_number),
            'balance' => 0
        ]);

        return back()->with('toast', [
            'title' => trans('public.toast_upgrade_to_agent_success'),
            'type' => 'success',
        ]);
    }

    public function detail($id) // $id_number
    {
        $user = User::find($id); // where('id_number', $id_number)->select('id', 'name')->first()

        return Inertia::render('Member/Listing/MemberDetail/MemberListingDetail', [
            'user' => $user,
            'teams' => (new GeneralController())->getTeams(true),
            'countries' => (new GeneralController())->getCountries(true),
            'uplines' => (new GeneralController())->getUplines(true),
        ]);
    }

    public function getUserData(Request $request)
    {
        $user = User::with(['upline:id,first_name', 'teamHasUser'])->find($request->id);

        $userData = [
            'id' => $user->id,
            'name' => $user->first_name,
            'email' => $user->email,
            'dial_code' => $user->dial_code,
            'phone' => $user->phone,
            'upline_id' => $user->upline_id,
            'role' => $user->role,
            'id_number' => $user->id_number,
            'status' => $user->status,
            // 'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'team_id' => $user->teamHasUser->team_id ?? null,
            'team_name' => $user->teamHasUser->team->name ?? null,
            'team_color' => $user->teamHasUser->team->color ?? null,
            'upline_name' => $user->upline->first_name ?? null,
            'total_direct_member' => $user->directChildren->where('role', 'member')->count(),
            'total_direct_agent' => $user->directChildren->where('role', 'agent')->count(),
            // 'kyc_verification' => $user->getFirstMedia('kyc_verification'),
            'kyc_approved_at' => $user->kyc_approved_at,
        ];

        $paymentAccounts = $user->paymentAccounts()
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'payment_account_name' => $account->payment_account_name,
                    'account_no' => $account->account_no,
                ];
            });

        return response()->json([
            'userDetail' => $userData,
            'paymentAccounts' => $paymentAccounts
        ]);
    }

    public function updateMemberInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user_id)],
            'name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'dial_code' => ['required'],
            'phone' => ['required', 'max:255'],
            'phone_number' => ['required', 'max:255', Rule::unique(User::class)->ignore($request->user_id)],
        ])->setAttributeNames([
            'email' => trans('public.email'),
            'name' => trans('public.name'),
            'dial_code' => trans('public.phone_code'),
            'phone' => trans('public.phone'),
            'phone_number' => trans('public.phone_number'),
        ]);
        $validator->validate();

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_member_info_success'),
            'type' => 'success'
        ]);
    }

    public function updateCryptoWalletInfo(Request $request)
    {
        $wallet_names = $request->wallet_name;
        $token_addresses = $request->token_address;

        $errors = [];

        // Validate wallets and addresses
        foreach ($wallet_names as $index => $wallet_name) {
            $token_address = $token_addresses[$index] ?? '';

            if (empty($wallet_name) && !empty($token_address)) {
                $errors["wallet_name.$index"] = trans('validation.required', ['attribute' => trans('public.wallet_name') . ' #' . ($index + 1)]);
            }

            if (!empty($wallet_name) && empty($token_address)) {
                $errors["token_address.$index"] = trans('validation.required', ['attribute' => trans('public.token_address') . ' #' . ($index + 1)]);
            }
        }

        foreach ($token_addresses as $index => $token_address) {
            $wallet_name = $wallet_names[$index] ?? '';

            if (empty($token_address) && !empty($wallet_name)) {
                $errors["token_address.$index"] = trans('validation.required', ['attribute' => trans('public.token_address') . ' #' . ($index + 1)]);
            }

            if (!empty($token_address) && empty($wallet_name)) {
                $errors["wallet_name.$index"] = trans('validation.required', ['attribute' => trans('public.wallet_name') . ' #' . ($index + 1)]);
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        if ($wallet_names && $token_addresses) {
            foreach ($wallet_names as $index => $wallet_name) {
                // Skip iteration if id or token_address is null
                if (is_null($token_addresses[$index])) {
                    continue;
                }

                $conditions = [
                    'user_id' => $request->user_id,
                ];

                // Check if 'id' is set and valid
                if (!empty($request->id[$index])) {
                    $conditions['id'] = $request->id[$index];
                } else {
                    $conditions['id'] = 0;
                }

                PaymentAccount::updateOrCreate(
                    $conditions,
                    [
                        'payment_account_name' => $wallet_name,
                        'payment_platform' => 'crypto',
                        'payment_platform_name' => 'USDT (TRC20)',
                        'account_no' => $token_addresses[$index],
                        'currency' => 'USDT'
                    ]
                );
            }
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_crypto_wallet_info_success'),
            'type' => 'success'
        ]);
    }

    // public function updateKYCStatus(Request $request)
    // {
    //     $user = User::find($request->id);

    //     $user->kyc_approved_at = null;
    //     $user->save();

    //     return redirect()->back()->with('toast', [
    //         'title' => trans('public.toast_kyc_upload_request'),
    //         'type' => 'success'
    //     ]);
    // }

    public function getFinancialInfoData(Request $request)
    {
        $query = Transaction::query()
            ->where('user_id', $request->id)
            ->where('status', 'successful')
            ->select('id', 'from_meta_login', 'to_meta_login', 'transaction_type', 'amount', 'transaction_amount', 'status', 'created_at');

        $total_deposit = (clone $query)->where('transaction_type', 'deposit')->sum('transaction_amount');
        $total_withdrawal = (clone $query)->where('transaction_type', 'withdrawal')->sum('amount');
        $transaction_history = $query->whereIn('transaction_type', ['deposit', 'withdrawal'])
            ->latest()
            ->get();

        $rebate_wallet = Wallet::where('user_id', $request->id)
            ->where('type', 'rebate_wallet')
            ->first();

        return response()->json([
            'totalDeposit' => $total_deposit,
            'totalWithdrawal' => $total_withdrawal,
            'transactionHistory' => $transaction_history,
            'rebateWallet' => $rebate_wallet,
        ]);
    }

    public function walletAdjustment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required'],
            'amount' => ['required', 'numeric', 'gt:1'],
            'remarks' => ['nullable'],
        ])->setAttributeNames([
            'action' => trans('public.action'),
            'amount' => trans('public.amount'),
            'remarks' => trans('public.remarks'),
        ]);
        $validator->validate();

        $action = $request->action;
        $amount = $request->amount;
        $wallet = Wallet::where('user_id', $request->user_id)->first();

        if ($action == 'rebate_out' && $wallet->balance < $amount) {
            throw ValidationException::withMessages(['amount' => trans('public.insufficient_balance')]);
        }

        Transaction::create([
            'user_id' => $wallet->user_id,
            'category' => 'wallet',
            'transaction_type' => $action,
            'from_wallet_id' => $action == 'rebate_out' ? $wallet->id : null,
            'to_wallet_id' => $action == 'rebate_in' ? $wallet->id : null,
            'transaction_number' => RunningNumberService::getID('transaction'),
            'amount' => $amount,
            'transaction_charges' => 0,
            'transaction_amount' => $amount,
            'old_wallet_amount' => $wallet->balance,
            'new_wallet_amount' => $action == 'rebate_out' ? $wallet->balance - $amount : $wallet->balance + $amount,
            'status' => 'successful',
            'remarks' => $request->remarks,
            'approved_at' => now(),
            'handle_by' => Auth::id(),
        ]);

        $wallet->balance = $action === 'rebate_out' ? $wallet->balance - $amount : $wallet->balance + $amount;
        $wallet->save();

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_rebate_adjustment_success'),
            'type' => 'success'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', Password::min(8)->letters()->numbers()->symbols()->mixedCase(), 'confirmed']
        ])->setAttributeNames([
            'password' => trans('public.password'),
        ]);
        $validator->validate();

        $user = User::find($request->id);
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_reset_password_success'),
            'type' => 'success'
        ]);

    }

    public function getTradingAccounts(Request $request)
    {
        // $cTraderService = new CTraderService;

        // $conn = $cTraderService->connectionStatus();
        // if ($conn['code'] != 0) {
        //     return back()
        //         ->with('toast', [
        //             'title' => 'Connection Error',
        //             'type' => 'error'
        //         ]);
        // }

        // try {
        //     $cTraderService->getUserInfo($request->meta_login);
        // } catch (\Throwable $e) {
        //     Log::error($e->getMessage());

        //     return back()
        //         ->with('toast', [
        //             'title' => 'No Account Found',
        //             'type' => 'error'
        //         ]);
        // }

        // Fetch trading accounts based on user ID
        $tradingAccounts = TradingAccount::query()
            ->where('user_id', $request->id)
            ->get() // Fetch the results from the database
            ->map(function($trading_account) {
                return [
                    'id' => $trading_account->id,
                    'meta_login' => $trading_account->meta_login,
                    'account_type' => $trading_account->accountType->name,
                    'balance' => $trading_account->balance,
                    'credit' => $trading_account->credit,
                    'equity' => $trading_account->equity,
                    'leverage' => $trading_account->margin_leverage,
                    // 'account_type_color' => $trading_account->accountType->color,
                    'updated_at' => $trading_account->updated_at,
                ];
            });

        // Return the response as JSON
        return response()->json([
            'tradingAccounts' => $tradingAccounts,
        ]);
    }

    public function getAdjustmentHistoryData(Request $request)
    {
        $adjustment_history = Transaction::where('user_id', $request->id)
            ->whereIn('transaction_type', ['rebate_in', 'rebate_out', 'balance_in','balance_out','credit_in','credit_out',])
            ->where('status', 'successful')
            ->latest()
            ->get();

        return response()->json($adjustment_history);
    }

    // public function uploadKyc(Request $request)
    // {
    //     dd($request->all());
    // }

    public function deleteMember(Request $request)
    {
        $user = User::find($request->id);

        $relatedUsers = User::where('hierarchyList', 'like', '%-' . $user->id . '-%')->get();

        foreach ($relatedUsers as $relatedUser) {
            $updatedHierarchyList = str_replace('-' . $user->id . '-', '-', $relatedUser->hierarchyList);

            $relatedUser->hierarchyList = $updatedHierarchyList;

            // Split the updated hierarchyList to find the new upline
            $hierarchyArray = array_filter(explode('-', $updatedHierarchyList));

            // Since the last element is the `upline_id`, find the new upline
            if (!empty($hierarchyArray)) {
                // Get the last element in the array, which is the new upline_id
                $newUplineId = end($hierarchyArray);
                $relatedUser->upline_id = $newUplineId;
            } else {
                $relatedUser->upline_id = null;
            }
            $relatedUser->save();
        }

        $user->transactions()->delete();
        $user->tradingAccounts()->delete();
        $user->tradingUsers()->delete();
        $user->paymentAccounts()->delete();
        $user->rebateAllocations()->delete();
        $user->delete();

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_delete_member_success'),
            'type' => 'success'
        ]);
    }

    public function access_portal(User $user)
    {
        $dataToHash = $user->name . $user->email . $user->id_number;
        $hashedToken = md5($dataToHash);

        $currentHost = $_SERVER['HTTP_HOST'];

        // Retrieve the app URL and parse its host
        $appUrl = parse_url(config('app.url'), PHP_URL_HOST);
        $memberProductionUrl = config('app.member_production_url');

        if ($currentHost === 'qcg-admin.currenttech.pro') {
            $url = "https://qcg-user.currenttech.pro/admin_login/$hashedToken";
        } elseif ($currentHost === $appUrl) {
            $url = "$memberProductionUrl/admin_login/$hashedToken";
        } else {
            return back();
        }

        $params = [
            'admin_id' => Auth::id(),
            'admin_name' => Auth::user()->name,
        ];

        $redirectUrl = $url . "?" . http_build_query($params);
        return Inertia::location($redirectUrl);
    }
}
