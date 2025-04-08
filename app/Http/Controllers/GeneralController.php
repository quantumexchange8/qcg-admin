<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Country;
use App\Models\AccountType;
use App\Models\LeaderboardBonus;
use App\Models\TeamHasUser;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TeamSettlement;
use App\Models\TradingAccount;
use App\Models\SettingLeverage;
use App\Models\TradeBrokerHistory;
use App\Models\TradeRebateSummary;
use App\Services\CTraderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GeneralController extends Controller
{
    public function getWalletData(Request $request, $returnAsArray = false)
    {
        $wallets = Wallet::where('user_id', $request->user_id)
                         ->where('type', 'rebate_wallet')
                         ->first();

        if ($returnAsArray) {
            return $wallets;
        }

        return response()->json([
            'walletData' => $wallets,
        ]);
    }

    public function getTradePointData(Request $request, $returnAsArray = false)
    {
        $trade_points = Wallet::where('user_id', $request->user_id)
                         ->where('type', 'trade_points')
                         ->first();

        if ($returnAsArray) {
            return $trade_points;
        }

        return response()->json([
            'tradePointData' => $trade_points,
        ]);
    }

    public function getTradingAccountData(Request $request, $returnAsArray = false)
    {
        $conn = (new CTraderService)->connectionStatus();
        if ($conn['code'] != 0) {
            return collect([
                'toast' => [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]
            ]);
        }

        $accounts = TradingAccount::where('user_id', $request->user_id)->get();
        $accountData = $accounts->map(function ($account) {
            try {
                (new CTraderService)->getUserInfo($account->meta_login);
                $updatedAccount = TradingAccount::where('meta_login', $account->meta_login)->first();

                return [
                    'meta_login' => $updatedAccount->meta_login,
                    'balance' => $updatedAccount->balance - $updatedAccount->credit,
                    'credit' => $updatedAccount->credit,
                ];
            } catch (\Throwable $e) {
                Log::error("Error processing account {$account->meta_login}: " . $e->getMessage());

                return [
                    'meta_login' => $account->meta_login,
                    'balance' => 0,
                    'credit' => 0,
                ];
            }
        });

        if ($returnAsArray) {
            return $accountData;
        }

        return collect(['accountData' => $accountData]);
    }

    public function updateAccountData(Request $request)
    {
        $conn = (new CTraderService)->connectionStatus();
        if ($conn['code'] != 0) {
            return collect([
                'toast' => [
                    'title' => 'Connection Error',
                    'type' => 'error'
                ]
            ]);
        }
        
        try {
            // Fetch updated account info using CTraderService
            (new CTraderService)->getUserInfo($request->meta_login);
    
            $updatedAccount = TradingAccount::where('meta_login', $request->meta_login)->first();
    
            return response()->json([
                'meta_login' => $updatedAccount->meta_login,
                'balance' => $updatedAccount->balance - $updatedAccount->credit,
                'credit' => $updatedAccount->credit,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error processing account {$request->meta_login}: " . $e->getMessage());
    
            return response()->json([
                'meta_login' => $request->meta_login,
                'balance' => 0,
                'credit' => 0,
            ]);
        }
    }
    
    public function getLeverages($returnAsArray = false)
    {
        $leverages = SettingLeverage::where('status', 'active')->get()
            ->map(function ($leverage) {
                return [
                    'name' => $leverage->leverage,
                    'value' => $leverage->value,
                ];
            });
        $leverages->prepend(['name' => 'Free', 'value' => 0]);

        if ($returnAsArray) {
            return $leverages;
        }

        return response()->json([
            'leverages' => $leverages,
        ]);
    }

    public function getTransactionMonths($returnAsArray = false)
    {
        $transactionDates = Transaction::pluck('created_at');
        $months = $transactionDates
            ->map(function ($date) {
                // Extract only the "F Y" format for uniqueness
                return Carbon::parse($date)->format('F Y');
            })
            ->unique()
            ->map(function ($month) {
                // Reformat the unique months to include "01" in front
                return '01 ' . $month;
            })
            ->reverse()
            ->values();
    
        // Add the current month at the end if it's not already in the list
        $currentMonth = '01 ' . Carbon::now()->format('F Y');
        if (!$months->contains($currentMonth)) {
            $months->prepend($currentMonth);
        }

        // Add custom date ranges at the top
        $additionalRanges = collect([
            'select_all',
            'last_week', 
            'last_2_week', 
            'last_3_week', 
        ]);

        $months = $additionalRanges->merge($months);
    
        if ($returnAsArray) {
            return $months;
        }
    
        return response()->json([
            'months' => $months,
        ]);
    }
    
    public function getIncentiveMonths($returnAsArray = false)
    {
        $incentiveDates = LeaderboardBonus::pluck('created_at');
        $months = $incentiveDates
            ->map(function ($date) {
                return Carbon::parse($date)->format('F Y');
            })
            ->unique()
            ->map(function ($month) {
                // Reformat the unique months to include "01" in front
                return '01 ' . $month;
            })
            ->reverse()
            ->values();

        // Add the current month at the end if it's not already in the list
        $currentMonth = '01 ' . Carbon::now()->format('F Y');
        if (!$months->contains($currentMonth)) {
            $months->prepend($currentMonth);
        }

        // Add custom date ranges at the top
        $additionalRanges = collect([
            'select_all',
            'last_week', 
            'last_2_week', 
            'last_3_week', 
        ]);

        $months = $additionalRanges->merge($months);

        if ($returnAsArray) {
            return $months;
        }

        return response()->json([
            'months' => $months,
        ]);
    }

    public function getAccountTypes($returnAsArray = false)
    {
        $accountTypes = AccountType::all()
            ->filter(function ($accountType) {
                return $accountType->slug !== 'demo_account';
            })
            ->map(function ($accountType) {
                return [
                    'value' => $accountType->id,
                    'name' => $accountType->name,
                    'slug' => $accountType->slug,
                    'category' => $accountType->category,
                ];
            });
    
        // Ensure that it returns an array when $returnAsArray is true
        if ($returnAsArray) {
            return $accountTypes->values()->all();  // .all() ensures it is a plain array
        }
        
        // If not, return it as a JSON response
        return response()->json([
            'accountTypes' => $accountTypes->values()->all(),
        ]);
    }
    
    public function getAccountTypesWithSlugs($returnAsArray = false)
    {
        $accountTypes = AccountType::all()
            ->filter(function ($accountType) {
                return $accountType->slug !== 'demo_account';
            })
            ->map(function ($accountType) {
                return [
                    'value' => $accountType->slug,
                    'name' => trans('public.' . $accountType->slug),
                    'category' => $accountType->category,
                ];
            });

        if ($returnAsArray) {
            return $accountTypes->values()->all();
        }

        return response()->json([
            'accountTypes' => $accountTypes->values()->all(),
        ]);
    }

    public function getSettlementMonths($returnAsArray = false)
    {
        $settledDates = TeamSettlement::pluck('transaction_start_at');
        $months = $settledDates
            ->map(function ($date) {
                return Carbon::parse($date)->format('F Y');
            })
            ->unique()
            ->map(function ($month) {
                // Reformat the unique months to include "01" in front
                return '01 ' . $month;
            })
            ->reverse()
            ->values();

        if ($returnAsArray) {
            return $months;
        }

        return response()->json([
            'months' => $months,
        ]);
    }

    public function getUplines($returnAsArray = false)
    {
        $uplines = User::whereIn('role', ['agent', 'member'])
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->first_name,
                    'email' => $user->email,
                    // 'profile_photo' => $user->getFirstMediaUrl('profile_photo')
                ];
            });

        if ($returnAsArray) {
            return $uplines;
        }

        return response()->json([
            'uplines' => $uplines,
        ]);
    }

    public function getCountries($returnAsArray = false)
    {
        $countries = Country::get()->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'phone_code' => $country->phone_code,
            ];
        });

        if ($returnAsArray) {
            return $countries;
        }

        return response()->json([
            'countries' => $countries,
        ]);
    }

    public function getTeams($returnAsArray = false)
    {
        $teams = Team::all()->map(function ($team) {
            return [
                'value' => $team->id,
                'name' => $team->name,
                'color' => $team->color,
            ];
        });

        if ($returnAsArray) {
            return $teams;
        }

        return response()->json([
            'teams' => $teams,
        ]);
    }

    public function getTradeMonths($returnAsArray = false)
    {
        // Group the trade records by month and year using database functions
        $months = TradeBrokerHistory::selectRaw("DATE_FORMAT(trade_close_time, '%m/%Y') as value, DATE_FORMAT(trade_close_time, '%M %Y') as name")
            ->groupBy('value', 'name')
            ->orderByRaw('MIN(trade_close_time) DESC') // Ensure the results are ordered by the most recent month
            ->get();
        
        // Add current month if it's not already in the list
        $currentMonth = Carbon::now()->format('m/Y');
        $currentMonthName = Carbon::now()->format('F Y');
    
        $monthsArray = $months->toArray();
        if (!in_array(['value' => $currentMonth, 'name' => $currentMonthName], $monthsArray)) {
            array_unshift($monthsArray, ['value' => $currentMonth, 'name' => $currentMonthName]);
        }

        $additionalRanges = [
            ['value' => 'select_all', 'name' => trans('public.select_all')],
            ['value' => 'last_week', 'name' => trans('public.last_week')],
            ['value' => 'last_2_week', 'name' => trans('public.last_2_week')],
            ['value' => 'last_3_week', 'name' => trans('public.last_3_week')],
        ];
    
        // Merge additional ranges with months
        $monthsArray = array_merge($additionalRanges, $monthsArray);
    
        // Return the result as either an array or a JSON response
        if ($returnAsArray) {
            return $monthsArray;
        }
    
        return response()->json([
            'months' => $monthsArray,
        ]);
    }

    public function getRebateMonths($returnAsArray = false)
    {
        $firstTransaction = TradeRebateSummary::oldest()
        ->value('execute_at'); // Get only the first transaction date

        $months = collect();

        if ($firstTransaction) {
            $firstMonth = Carbon::parse($firstTransaction)->startOfMonth();
            $currentMonth = Carbon::now()->startOfMonth();

            // Generate all months from first transaction to current month
            while ($firstMonth <= $currentMonth) {
                $months->push('01 ' . $firstMonth->format('F Y'));
                $firstMonth->addMonth();
            }

            $months = $months->reverse()->values();
        }

        // Add custom date ranges at the top
        $additionalRanges = collect([
            'select_all',
            'last_week', 
            'last_2_week', 
            'last_3_week', 
        ]);

        $months = $additionalRanges->merge($months);

        if ($returnAsArray) {
            return $months;
        }

        return response()->json([
            'months' => $months,
        ]);
    }
    
    public function getVisibleToOptions($returnAsArray = false)
    {
        $teams = Team::with('team_has_user.user:id,first_name')->get();
    
        // Initialize an array to hold the transformed data
        $visibleToOptions = [];
    
        // Loop through each team
        foreach ($teams as $team) {
            $members = [];
    
            // Loop through each member in the team
            foreach ($team->team_has_user as $teamHasUser) {
                $members[] = [
                    'label' => $teamHasUser->user->first_name,
                    'value' => $teamHasUser->user->id,
                ];
            }
    
            // Add the team's data to the options array
            $visibleToOptions[] = [
                'value' => $team->id,
                'name' => $team->name,
                'color' => $team->color,
                'members' => $members,
            ];
        }
    
        // Return the result as either an array or a JSON response
        if ($returnAsArray) {
            return $visibleToOptions;
        }
    
        return response()->json([
            'visibleToOptions' => $visibleToOptions,
        ]);
    }

    public function getKycMonths($returnAsArray = false)
    {
        $firstVerifiedDate = User::orderBy('kyc_approved_at')->value('kyc_approved_at');
        $start = Carbon::parse($firstVerifiedDate)->startOfMonth();
        $end = Carbon::now()->startOfMonth();

        $months = collect();
        while ($start <= $end) {
            $months->push('01 ' . $start->format('F Y'));
            $start->addMonth();
        }
    
        // Add the current month at the end if it's not already in the list
        $currentMonth = '01 ' . Carbon::now()->format('F Y');
        if (!$months->contains($currentMonth)) {
            $months->prepend($currentMonth);
        }

        // Add custom date ranges at the top
        $additionalRanges = collect([
            'select_all',
            'last_week', 
            'last_2_week', 
            'last_3_week', 
        ]);

        $months = $additionalRanges->merge($months);
    
        if ($returnAsArray) {
            return $months;
        }
    
        return response()->json([
            'months' => $months,
        ]);
    }
            
}
