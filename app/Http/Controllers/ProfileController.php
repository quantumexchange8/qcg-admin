<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use App\Services\CTraderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Services\DropdownOptionService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;

            return redirect()->back()->with('toast', [
                'title' => 'Invalid Action',
                'type' => 'warning'
            ]);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_profile_success'),
            'type' => 'success'
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

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
                    $cTraderService->getUserInfo($tradingAccount->meta_login);
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
    
        // If trading accounts or users do not exist, handle user deletion without cTrader logic
        $relatedUsers = User::where('hierarchyList', 'like', '%-' . $user->id . '-%')->get();
    
        foreach ($relatedUsers as $relatedUser) {
            $updatedHierarchyList = str_replace('-' . $user->id . '-', '-', $relatedUser->hierarchyList);
            $relatedUser->hierarchyList = $updatedHierarchyList;
    
            // Update the upline
            $hierarchyArray = array_filter(explode('-', $updatedHierarchyList));
            $relatedUser->upline_id = !empty($hierarchyArray) ? end($hierarchyArray) : null;
    
            $relatedUser->save();
        }
        
        $user->paymentAccounts()->delete();
        $user->tradingAccounts()->delete();
        $user->tradingUsers()->delete();
        $user->transactions()->delete();
        $user->rebateAllocations()->delete();
        $user->rebate_wallet()->delete();
        $user->incentive_wallet()->delete();
        $user->teamHasUser()->delete();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function updateProfilePhoto(Request $request)
    {
        $user = $request->user();

        if ($request->action == 'upload' && $request->hasFile('profile_photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMedia($request->profile_photo)->toMediaCollection('profile_photo');
        }

        if ($request->action == 'remove') {
            $user->clearMediaCollection('profile_photo');
        }

        return redirect()->back()->with('toast', [
            'title' => trans('public.toast_update_profile_photo_success'),
            'type' => 'success'
        ]);
    }

}
