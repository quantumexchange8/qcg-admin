<?php

namespace App\Http\Controllers;

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


    public function updatePromotionConfiguration(Request $request)
    {
        dd($request->all());
        // Find the account type by ID
        $account_type = AccountType::find($request->id);
    
        // Update the account type fields
        $account_type->category = $request->category;
        $account_type->descriptions = json_encode($request->descriptions);
        $account_type->leverage = $request->leverage;
        $account_type->trade_open_duration = $request->trade_delay_duration;
        $account_type->maximum_account_number = $request->max_account;
        $account_type->color = $request->color;
        $account_type->promotion_title = $request->promotion_title;
        $account_type->promotion_description = $request->promotion_description;
        $account_type->promotion_period_type = $request->promotion_period_type;
        $account_type->promotion_period = $request->promotion_period;
        $account_type->promotion_type = $request->promotion_type;
        $account_type->minimum_target = $request->minimum_target;
        $account_type->bonus_type = $request->bonus_type;
        $account_type->bonus_amount_type = $request->bonus_amount_type;
        $account_type->bonus_amount = $request->bonus_amount;
        $account_type->maximum_bonus_cap = $request->maximum_bonus_cap;
        $account_type->applicable_deposit = $request->applicable_deposit;
        $account_type->credit_withdraw_policy = $request->credit_withdraw_policy;
        $account_type->credit_withdraw_date_period = $request->credit_withdraw_date_period;
        $account_type->visible_to = $request->visible_to;
    
        // Handle the members array if provided
        if ($request->has('members')) {
            $account_type->members = $request->members;
        }
    
        // Save the updated account type
        $account_type->save();
    
        return back()->with('toast', [
            'title' => $account_type->status == 'active' ? trans("public.toast_account_type_activated") : trans("public.toast_account_type_deactivated"),
            'type' => 'success',
        ]);
    }
    
}
