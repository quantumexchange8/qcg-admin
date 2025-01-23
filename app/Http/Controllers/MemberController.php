<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\ForumPost;
use App\Models\AccountType;
use App\Models\TradingUser;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use App\Models\TradingAccount;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Models\RebateAllocation;
use App\Services\CTraderService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Exports\MemberListingExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\TeamMemberAssignmentJob;
use App\Services\RunningNumberService;
use App\Http\Requests\AddMemberRequest;
use App\Services\DropdownOptionService;
use App\Services\Data\UpdateTradingUser;
use App\Services\ChangeTraderBalanceType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Http\Controllers\GeneralController;
use App\Services\Data\UpdateTradingAccount;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function listing()
    {
        // // Retrieve all members and agents
        // $users = User::whereIn('role', ['member', 'agent'])->where('id_number', null)->get();

        // // Iterate over each user to set their id_number
        // foreach ($users as $user) {
        //     // Generate id_number based on role
        //     if ($user->role === 'agent') {
        //         $user->id_number = 'AID' . Str::padLeft($user->id, 5, "0");
        //     } elseif ($user->role === 'member') {
        //         $user->id_number = 'MID' . Str::padLeft($user->id, 5, "0");
        //     }

        //     // Save the updated id_number back to the database
        //     $user->save();
        // }

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

        $users = User::with(['teamHasUser.team'])
            // ->whereNot('role', 'super-admin')
            ->when($role, function ($query, $role) {
                return $query->where('role', $role);
            })
            ->latest()
            ->paginate(10);

        // Count the number of users where role is 'member' and 'agent'
        $memberCount = User::where('role', 'member')->count();
        $agentCount = User::where('role', 'agent')->count();

        return response()->json([
            'users' => $users,
            'total_members' => $memberCount,
            'total_agents' => $agentCount,
        ]);
    }

    public function getMemberListingPaginate(Request $request)
    {

        $type = $request->input('type');
        $query = User::with(['teamHasUser.team'])
            ->where('role', $type);

        $search = $request->input('search');
        if ($search) {
            $query->where(function ($query) use ($search) {
                $keyword = $search;

                $query->where('first_name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('id_number', 'like', '%' . $keyword . '%');
            });
        }

        $team_id = $request->input('team_id');
        if ($team_id) {
            $query->whereHas('teamHasUser', function ($query) use ($team_id) {
                $query->where('team_id', $team_id);
            });
        }

        // Handle sorting
        $sortField = $request->input('sortField', 'created_at'); // Default to 'created_at'
        $sortOrder = $request->input('sortOrder', -1); // 1 for ascending, -1 for descending
        $query->orderBy($sortField, $sortOrder == 1 ? 'asc' : 'desc');

        // Handle pagination
        $rowsPerPage = $request->input('rows', 15); // Default to 15 if 'rows' not provided
        $currentPage = $request->input('page', 0) + 1; // Laravel uses 1-based page numbers, PrimeVue uses 0-based
        
        // Export logic
        if ($request->has('exportStatus') && $request->exportStatus === 'true') {
            $members = $query; // Fetch all members for export
            return Excel::download(new MemberListingExport($members), now() . '-members.xlsx');
        }

        $users = $query
            ->select([
                'id',
                'first_name',
                'email',
                'id_number',
                'role',
                'email_verified_at',
                'status',
                'upline_id',
                'hierarchyList'
            ])
            ->paginate($rowsPerPage, ['*'], 'page', $currentPage);

        $memberCount = User::where('role', 'member')->count();
        $agentCount = User::where('role', 'agent')->count();

        return response()->json([
            'success' => true,
            'data' => $users,
            'total_members' => $memberCount,
            'total_agents' => $agentCount,
        ]);
    }

    public function addNewMember(AddMemberRequest $request)
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

        $user = User::create([
            'first_name' => $request->name,
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
            'role' => 'member',
            'kyc_approval' => 'verified',
        ]);

        $user->setReferralId();

        $id_no = ($user->role == 'agent' ? 'AID' : 'MID') . Str::padLeft($user->id, 5, "0");
        $user->id_number = $id_no;
        $user->save();

        $user->syncRoles('member');

        // create ct id to link ctrader account
        if (App::environment('production')) {
            $ctUser = (new CTraderService)->CreateCTID($user->email);
            $user->ct_user_id = $ctUser['userId'];
            $user->save();
        }

        if ($upline->teamHasUser) {
            $user->assignedTeam($upline->teamHasUser->team_id);
        }

        Wallet::create([
            'user_id' => $user->id,
            'type' => 'rebate_wallet',
            'address' => 'RB' . Str::padLeft($user->id, 5, "0"),
            'balance' => 0
        ]);

        if ($user->role == 'agent') {
            $user->syncRoles('agent');

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
        // $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        return back()->with('toast', [
            'title' => trans($user->status === 'active' ? 'public.toast_member_has_activated' : 'public.toast_member_has_deactivated'),
            'type' => 'success',
        ]);
    }

    public function getAvailableUplines(Request $request)
    {
        $role = $request->input('role', ['agent', 'member']);

        $memberId = $request->input('id');

        // Fetch the member and get their children (downline) IDs
        $member = User::findOrFail($memberId);
        $excludedIds = $member->getChildrenIds();
        $excludedIds[] = $memberId;

        // Fetch uplines who are not in the excluded list
        $uplines = User::whereIn('role', (array) $role)
            ->whereNotIn('id', $excludedIds)
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                    'email' => $user->email,
                ];
            });

        // Return the uplines as JSON
        return response()->json([
            'uplines' => $uplines
        ]);
    }

    public function transferUpline(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'user_id'   => 'required|exists:users,id',
            'upline_id' => 'required|exists:users,id',
            'role'      => 'required|in:agent,member',
        ]);

        // Find the user to be transferred
        $user = User::findOrFail($request->input('user_id'));

        // Check if the new upline is valid and not the same as the current one
        if ($user->upline_id === $request->input('upline_id')) {
            return back()->with('toast', [
                'title' => trans('public.transfer_same_upline_warning'),
                'type'  => 'warning',
            ]);
        }

        // Find the new upline
        $newUpline = User::findOrFail($request->input('upline_id'));

        // Step 1: Update the user's hierarchyList to reflect the new upline's hierarchy and ID
        $user->hierarchyList = $newUpline->hierarchyList . $newUpline->id . '-';
        $user->upline_id = $newUpline->id;

        // Update the user's team relationship
        if ($newUpline->teamHasUser) {
            $user->assignedTeam($newUpline->teamHasUser->team_id);
        }

        // Save the updated hierarchyList and upline_id for the user
        $user->save();

        // Step 2: If the role is 'agent' for the transferred user, set their RebateAllocation amounts to 0
        if ($request->role === 'agent') {
            // Retrieve all RebateAllocations for the user
            $rebateAllocations = RebateAllocation::where('user_id', $user->id)->get();

            // Set the amount of each RebateAllocation to 0
            foreach ($rebateAllocations as $rebateAllocation) {
                $rebateAllocation->amount = 0; // Set amount to 0
                $rebateAllocation->save();      // Save the updated record
            }
        }

        // Step 3: Update related users' hierarchyList and their RebateAllocation amounts if they are agents
        $relatedUsers = User::where('hierarchyList', 'like', '%-' . $user->id . '-%')->get();

        foreach ($relatedUsers as $relatedUser) {
            $userIdSegment = '-' . $user->id . '-';

            // Find the position of `-user_id-` in the related user's hierarchyList
            $pos = strpos($relatedUser->hierarchyList, $userIdSegment);

            if ($pos !== false) {
                // Extract the part after the user's ID segment (tail part)
                $tailHierarchy = substr($relatedUser->hierarchyList, $pos + strlen($userIdSegment));

                // Prepend the user's new hierarchyList + user ID to the tail part
                $relatedUser->hierarchyList = $user->hierarchyList . $user->id . '-' . $tailHierarchy;
            }

            // Save the updated hierarchyList for the related user
            $relatedUser->save();

            // Step 4: If the related user is an agent, set their RebateAllocation amounts to 0
            if ($relatedUser->role === 'agent') {
                RebateAllocation::where('user_id', $relatedUser->id)->update(['amount' => 0]);
            }
        }

        // Step 5 update the related user team has user as transfer upline will change team as well
        // Get the team_id from the new upline's teamHasUser relationship
        $team_id = $newUpline->teamHasUser->team_id;

        $relatedUserIds = $relatedUsers->pluck('id')->toArray();

        // If the team_id is valid, dispatch the job
        if ($team_id) {
            // Dispatch with all user IDs and team_id, similar to your example
            TeamMemberAssignmentJob::dispatch($relatedUserIds, $team_id);
        }

        // Return a success response
        return back()->with('toast', [
            'title' => trans('public.toast_transfer_upline_success'),
            'type'  => 'success',
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

        if (!$availableUpline) {
            return response()->json(['error' => 'No valid upline found'], 404);
        }

        $uplineRebate = RebateAllocation::with('symbol_group:id,display')
            ->where('user_id', $availableUpline->id)
            ->get();

        $availableAccountTypeId = [];

        if (!$uplineRebate->isEmpty()) {
            $availableAccountTypeId = $uplineRebate->pluck('account_type_id')->toArray();
        }

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
            'rebateDetails' => $uplineRebate,
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

        // Overwrite existing roles and assign 'agent' role
        $user->syncRoles('agent'); // This will remove any other roles and assign only 'agent'

        if (!$user->rebate_wallet) {
            Wallet::create([
                'user_id' => $user->id,
                'type' => 'rebate_wallet',
                'address' => str_replace('AID', 'RB', $user->id_number),
                'balance' => 0
            ]);
        }

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
            'email_verified_at' => $user->email_verified_at,
            'status' => $user->status,
            'profile_photo' => $user->getFirstMediaUrl('profile_photo'),
            'team_id' => $user->teamHasUser->team_id ?? null,
            'team_name' => $user->teamHasUser->team->name ?? null,
            'team_color' => $user->teamHasUser->team->color ?? null,
            'upline_name' => $user->upline->first_name ?? null,
            'total_direct_member' => $user->directChildren->where('role', 'member')->count(),
            'total_direct_agent' => $user->directChildren->where('role', 'agent')->count(),
            'kyc_verification' => $user->getFirstMedia('kyc_verification'),
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
            'name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user_id)],
            'dial_code' => ['required'],
            'phone' => ['required', 'regex:/^[0-9]+$/'],
            'phone_number' => ['required', Rule::unique(User::class)->ignore($request->user_id)],
        ])->setAttributeNames([
            'name' => trans('public.name'),
            'email' => trans('public.email'),
            'dial_code' => trans('public.phone_code'),
            'phone' => trans('public.phone_number'),
            'phone_number' => trans('public.phone_number'),
        ]);
        $validator->validate();

        $user = User::findOrFail($request->user_id);
        $user->update([
            'first_name' => $request->name,
            'email' => $request->email,
            'dial_code' => $request->dial_code['phone_code'],
            'phone' => $request->phone,
            'phone_number' => $request->phone_number,
        ]);

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

    public function updateKYCStatus(Request $request)
    {
        $user = User::find($request->id);

        $user->kyc_approved_at = null;
        $user->save();

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_kyc_upload_request'),
            'type' => 'success'
        ]);
    }

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
            'amount' => ['required', 'numeric', 'gt:0'],
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
            'category' => 'rebate_wallet',
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
            'password' => ['required', Password::min(8)->letters()->numbers()->symbols()->mixedCase(), 'confirmed'],
            'password_confirmation' => ['required','same:password'],
        ])->setAttributeNames([
            'password' => trans('public.password'),
            'password_confirmation' => trans('public.confirm_password')
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
        $user = User::find($request->id);
        $tradingAccounts = $user->tradingAccounts;

        if (App::environment('production')) {
            // Proceed only if there are associated trading accounts
            if ($tradingAccounts->isNotEmpty()) {
                $cTraderService = new CTraderService();

                // Check connection status
                $conn = $cTraderService->connectionStatus();
                if ($conn['code'] != 0) {
                    return back()->with('toast', [
                        'title' => 'Connection Error',
                        'type' => 'error'
                    ]);
                }

                // Iterate through trading accounts to get user info
                foreach ($tradingAccounts as $tradingAccount) {
                    try {
                        // Get user info from cTrader service using the meta_login
                        $cTraderService->getUserInfo($tradingAccount->meta_login);
                    } catch (\Throwable $e) {
                        Log::error("Error fetching user info for {$tradingAccount->meta_login}: " . $e->getMessage());
                        return back()->with('toast', [
                            'title' => 'Error Fetching Account Info',
                            'type' => 'error'
                        ]);
                    }
                }
            }
        }

        $inactiveThreshold = now()->subDays(90)->startOfDay();

        // Fetch trading accounts based on user ID with eager loading
        $tradingAccounts = TradingAccount::with([
            'trading_user:id,meta_login,last_access',
            'accountType:id,slug,color'  // Load account type info
        ])
        ->where('user_id', $request->id)
        ->get()  // Fetch the results from the database
        ->map(function ($trading_account) use ($inactiveThreshold) {
            // Access the latest transaction directly from transactions
            $lastTransaction = $trading_account->transactions()
                                            ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                                            ->where('created_at', '>=', $inactiveThreshold)
                                            ->latest()  // Order by latest transaction
                                            ->first();  // Get the latest transaction (or null if none)

            // Get the last access date of the trading user
            $lastAccess = $trading_account->trading_user->last_access;

            // Determine if the account is active based on:
            // 1. Account creation date (within the last 90 days),
            // 2. Last transaction date (within the last 90 days),
            $isActive = $trading_account->created_at >= $inactiveThreshold || // Account created within last 90 days
                    ($lastTransaction && $lastTransaction->created_at >= $inactiveThreshold); // Last transaction within 90 days

            return [
                'id' => $trading_account->id,
                'meta_login' => $trading_account->meta_login,
                'account_type' => $trading_account->accountType->slug,
                'color' => $trading_account->accountType->color,
                'balance' => $trading_account->balance,
                'credit' => $trading_account->credit,
                'equity' => $trading_account->equity,
                'leverage' => $trading_account->margin_leverage,
                'last_access' => $lastAccess,
                'is_active' => $isActive,
                'status' => $trading_account->status,
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

    public function uploadKyc(Request $request)
    {
        dd($request->all());
    }

    public function deleteMember(Request $request)
    {
        // Find the user by ID
        $user = User::find($request->id);

        if (!$user) {
            return back()->with('toast', [
                'title' => 'User Not Found',
                'type' => 'error'
            ]);
        }

        // Check for associated trading accounts and users
        $tradingAccounts = $user->tradingAccounts;
        $tradingUsers = $user->tradingUsers;

        // Proceed with cTrader logic only if both trading accounts or trading users are not empty
        if ($tradingAccounts->isNotEmpty() || $tradingUsers->isNotEmpty()) {
            $cTraderService = new CTraderService();

            // Check connection status
            $conn = $cTraderService->connectionStatus();
            if ($conn['code'] != 0) {
                return back()->with('toast', [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]);
            }

            // Iterate through trading accounts and users
            foreach ($tradingAccounts as $tradingAccount) {
                // Get user info from cTrader service
                try {
                    $accData = (new CTraderService())->getUser($tradingAccount->meta_login);

                    if (empty($accData)) {
                        $tradingAccount->trading_user->delete();
                        $tradingAccount->delete();
                    } else {
                        // Proceed with updating tradingAccount information
                        (new UpdateTradingUser)->execute($tradingAccount->meta_login, $accData);
                        (new UpdateTradingAccount)->execute($tradingAccount->meta_login, $accData);
                    }
                } catch (\Throwable $e) {
                    Log::error($e->getMessage());
                    return back()->with('toast', [
                        'title' => 'No Account Found',
                        'type' => 'error'
                    ]);
                }

                // Check if the account has a balance or equity
                if ($tradingAccount->balance > 0 || $tradingAccount->equity > 0 || $tradingAccount->credit > 0 || $tradingAccount->cash_equity > 0) {
                    return back()->with('toast', [
                        'title' => trans('public.account_have_balance'),
                        'type' => 'error'
                    ]);
                }

                // Attempt to delete the trading account
                try {
                    $cTraderService->deleteTrader($tradingAccount->meta_login);
                    $tradingAccount->trading_user->delete();
                    $tradingAccount->delete();
                } catch (\Throwable $e) {
                    Log::error('Failed to delete trading account: ' . $e->getMessage());
                    return back()->with('toast', [
                        'title' => 'No Account Found',
                        'type' => 'error'
                    ]);
                }
            }
        }

        // Get the upline's team ID using the upline relationship
        $teamId = $user->upline?->teamHasUser->team_id ?? 1;

        // If trading accounts or users do not exist, handle user deletion without cTrader logic
        $relatedUsers = User::where('hierarchyList', 'like', '%-' . $user->id . '-%')->get();

        foreach ($relatedUsers as $relatedUser) {
            $updatedHierarchyList = str_replace('-' . $user->id . '-', '-', $relatedUser->hierarchyList);
            $relatedUser->hierarchyList = $updatedHierarchyList;

            // Update the upline
            $hierarchyArray = array_filter(explode('-', $updatedHierarchyList));
            $relatedUser->upline_id = !empty($hierarchyArray) ? end($hierarchyArray) : null;

            $relatedUser->assignedTeam($teamId);

            $relatedUser->save();
        }

        // Get all interactions for the user
        $interactions = $user->interactions;

        // Calculate adjustments for likes and dislikes
        $userLikes = $interactions->where('type', 'like')->count();
        $userDislikes = $interactions->where('type', 'dislike')->count();

        // Get all posts interacted by the user
        $posts = $interactions->pluck('post_id')->unique();

        // Update post counts for each interacted post
        foreach ($posts as $postId) {
            $post = ForumPost::find($postId);

            if ($post) { 
                $post->update([
                    'total_likes_count' => $post->total_likes_count - $userLikes,
                    'total_dislikes_count' => $post->total_dislikes_count - $userDislikes,
                ]);
            }
        }

        // Delete all related data for the user
        $user->transactions()->delete();
        $user->paymentAccounts()->delete();
        $user->rebateAllocations()->delete();
        $user->teamHasUser()->delete();
        $user->rebate_wallet()->delete();
        $user->incentive_wallet()->delete();
        $user->interactions()->delete();
        // Remove roles and permissions
        if ($user->roles()->exists()) {
            $user->roles()->detach(); // Detach all roles
        }

        if ($user->permissions()->exists()) {
            $user->permissions()->detach(); // Detach all permissions
        }
        $user->delete();

        // Return success response for user deletion
        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_delete_member_success'),
            'type' => 'success'
        ]);
    }

    public function access_portal(User $user)
    {
        $dataToHash = $user->first_name . $user->email . $user->id_number;
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
            'admin_name' => Auth::user()->first_name,
        ];

        $redirectUrl = $url . "?" . http_build_query($params);
        return Inertia::location($redirectUrl);
    }
}
