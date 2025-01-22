<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccountTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('AccountType/AccountType', [
            'leverages' => (new GeneralController())->getLeverages(true),
        ]);
    }

    public function accountTypeConfiguration(Request $request)
    {
        $accountType = AccountType::find($request->id);
        
        return Inertia::render('AccountType/Partials/AccountTypeConfiguration', [
            'accountType' => $accountType,
            'leverages' => (new GeneralController())->getLeverages(true),
            'visibleToOptions' => (new GeneralController())->getVisibleToOptions(true),
        ]);

    }

    public function getAccountTypes()
    {
        $accountTypes = AccountType::get()
            ->map(function($accountType) {
                $locale = app()->getLocale();
                $translations = json_decode($accountType->descriptions, true);

                if ($accountType->trade_open_duration >= 60) {
                    $accountType['trade_delay'] = ($accountType->trade_open_duration / 60).' min';
                } else {
                    $accountType['trade_delay'] = $accountType->trade_open_duration. ' sec';
                }

                // need to change to calculate total account created for each type
                $accountType['total_account'] = $accountType->trading_accounts()->count();
                $accountType['description_locale'] = $translations[$locale] ?? '-';
                $accountType['description_en'] = $translations['en'] ?? '-';
                $accountType['description_tw'] = $translations['tw'] ?? '-';

                return $accountType;
            });

        return response()->json(['accountTypes' => $accountTypes]);
    }

    public function syncAccountTypes()
    {
        //function

        return back()->with('toast', [
            'title' => trans('public.toast_sync_account_type'),
            'type'=> 'success',
        ]);
    }

    public function updateAccountType(Request $request)
    {
        // Validate the request data using Validator facade
        $validator = Validator::make($request->all(), [
            'account_type_name' => ['required'],
            'category' => ['required'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string'],
            'leverage' => ['required', 'numeric'],
            'trade_delay_duration' => ['required'],
            'max_account' => ['required', 'numeric'],
            'color' => ['required'],
        ])->setAttributeNames([
            'account_type_name' => trans('public.account_type_name'),
            'category' => trans('public.category'),
            'descriptions.*' => trans('public.description'),
            'leverage' => trans('public.leverage'),
            'trade_delay_duration' => trans('public.trade_delay_duration'),
            'max_account' => trans('public.max_account'),
            'color' => trans('public.colour'),
        ]);

        $validator->validate();
    
        // Find the account type by ID
        $account_type = AccountType::findOrFail($request->id);
        
        // Update the account type properties
        $account_type->category = $request->category;
        $account_type->descriptions = json_encode($request->descriptions);
        $account_type->leverage = $request->leverage;
        $account_type->trade_open_duration = $request->trade_delay_duration;
        $account_type->maximum_account_number = $request->max_account;
        $account_type->color = $request->color;
        $account_type->status = 'active';
        $account_type->edited_by = Auth::id();
        $account_type->save();

        return back()->with('toast', [
            'title' => trans('public.toast_update_account_type_success'),
            'type' => 'success',
        ]);
    }

    public function updateStatus(Request $request)
    {
        $account_type = AccountType::find($request->id);
        $account_type->status = $account_type->status == 'active' ? 'inactive' : 'active';
        $account_type->save();

        return back()->with('toast', [
            'title' => $account_type->status == 'active' ? trans("public.toast_account_type_activated") : trans("public.toast_account_type_deactivated"),
            'type' => 'success',
        ]);
    }

    public function getVisibleToOptions(Request $request)
    {
        $search = $request->input('search');
    
        // Initialize the query to fetch teams with related users
        $teamsQuery = Team::with(['team_has_user.user:id,first_name,email,id_number']);
    
        // Apply search condition if search term is provided
        if ($search) {
            // Filter teams based on their name and users based on their details
            $teamsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhereHas('team_has_user.user', function ($query) use ($search) {
                          $query->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('id_number', 'like', '%' . $search . '%');
                      });
            });
        }
    
        // Execute the query to get the teams
        $teams = $teamsQuery->get();
    
        // Initialize an array to hold the transformed data
        $visibleToOptions = [];
    
        // Loop through each team
        foreach ($teams as $team) {
            $members = [];
    
            // Loop through each member in the team and add them if they match the search criteria
            foreach ($team->team_has_user as $teamHasUser) {
                $user = $teamHasUser->user;
    
                // Only add the user if they match the search criteria (no PHP string functions used)
                if ($search) {
                    // Check if any of the user's details match the search
                    if (
                        stripos($user->first_name, $search) !== false ||
                        stripos($user->email, $search) !== false ||
                        stripos($user->id_number, $search) !== false
                    ) {
                        $members[] = [
                            'label' => $user->first_name,
                            'value' => $user->id,
                        ];
                    }
                } else {
                    // If no search term is provided, include all members
                    $members[] = [
                        'label' => $user->first_name,
                        'value' => $user->id,
                    ];
                }
            }
    
            // Only add teams with members to the options array
            if (count($members) > 0) {
                $visibleToOptions[] = [
                    'value' => $team->id,
                    'name' => $team->name,
                    'color' => $team->color,
                    'members' => $members,
                ];
            }
        }
    
        return response()->json([
            'visibleToOptions' => $visibleToOptions,
        ]);
    }
        
    public function updatePromotionConfiguration(Request $request)
    {
        $promotionPeriodType = $request->input('promotion_period_type');
        $promotionType = $request->input('promotion_type');
        $creditWithdrawPolicy = $request->input('credit_withdraw_policy');
    
        // Validate the request data using Validator facade
        $validator = Validator::make($request->all(), [
            'account_type_name' => ['required'],
            'category' => ['required'],
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string'],
            'leverage' => ['required', 'numeric'],
            'trade_delay_duration' => ['required'],
            'max_account' => ['required', 'numeric'],
            'color' => ['required'],
            'visible_to' => ['required'],
            'members' => ['nullable', 'array'],
            'promotion_title' => ['required'],
            'promotion_description' => ['required'],
            'promotion_period_type' => ['required'],
            'promotion_period' => ['nullable', $promotionPeriodType === 'no_expiry_date' ? 'nullable' : 'required'],
            'promotion_type' => ['required'],
            'target_amount' => ['required'],
            'bonus_type' => ['required'],
            'bonus_amount_type' => ['required'],
            'bonus_amount' => ['required'],
            'maximum_bonus_cap' => ['nullable', $promotionType === 'deposit' ? 'required' : 'nullable'],
            'applicable_deposit' => ['nullable', $promotionType === 'deposit' ? 'required' : 'nullable'],
            'credit_withdraw_policy' => ['required'],
            'credit_withdraw_date_period' => ['nullable', $creditWithdrawPolicy === 'no_withdraw' ? 'nullable' : 'required'],
        ])->setAttributeNames([
            'account_type_name' => trans('public.account_type_name'),
            'category' => trans('public.category'),
            'descriptions.*' => trans('public.description'),
            'leverage' => trans('public.leverage'),
            'trade_delay_duration' => trans('public.trade_delay_duration'),
            'max_account' => trans('public.max_account'),
            'visible_to' => trans('public.visible_to'),
            'members' => trans('public.visible_to_selected_members'),
            'promotion_title' => trans('public.promotion_title'),
            'promotion_description' => trans('public.promotion_description'),
            'promotion_period_type' => trans('public.promotion_period'),
            'promotion_period' => trans('public.date'),
            'promotion_type' => trans('public.promotion_type'),
            'target_amount' => $promotionType === 'deposit' ? trans('public.minimum_deposit_amount') : trans('public.minimum_trade_lot_target'),
            'bonus_type' => trans('public.bonus_type'),
            'bonus_amount_type' => trans('public.bonus_amount_type'),
            'bonus_amount' => trans('public.amount'),
            'maximum_bonus_cap' => trans('public.maximum_bonus_cap'),
            'applicable_deposit' => trans('public.applicable_deposit'),
            'credit_withdraw_policy' => trans('public.credit_withdraw_policy'),
            'credit_withdraw_date_period' => trans('public.date'),
        ]);
    
        $validator->validate();
        
        // Find the account type by ID
        $account_type = AccountType::find($request->id);
    
        // Update the account type fields
        $account_type->category = $request->category;
        $account_type->descriptions = json_encode($request->descriptions);
        $account_type->leverage = $request->leverage;
        $account_type->trade_open_duration = $request->trade_delay_duration;
        $account_type->maximum_account_number = $request->max_account;
        $account_type->color = $request->color;
        $account_type->visible_to = $request->visible_to;
        $account_type->promotion_title = $request->promotion_title;
        $account_type->promotion_description = $request->promotion_description;
        $account_type->promotion_period_type = $request->promotion_period_type;
        $account_type->promotion_period = $request->promotion_period;
        $account_type->promotion_type = $request->promotion_type;
        $account_type->target_amount = $request->target_amount;
        $account_type->bonus_type = $request->bonus_type;
        $account_type->bonus_amount_type = $request->bonus_amount_type;
        $account_type->bonus_amount = $request->bonus_amount;
        $account_type->maximum_bonus_cap = $request->maximum_bonus_cap;
        $account_type->applicable_deposit = $request->applicable_deposit;
        $account_type->credit_withdraw_policy = $request->credit_withdraw_policy;
        $account_type->credit_withdraw_date_period = $request->credit_withdraw_date_period;
    
        // Save the updated account type
        // $account_type->status = 'active';
        $account_type->save();
    
        // Redirect to the index page with toast message
        return redirect()->route('accountType')->with('toast', [
            'title' => trans("public.toast_update_account_type_success"),
            'type' => 'success',
        ]);
    }
        
}
