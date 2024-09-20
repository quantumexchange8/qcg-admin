<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\AccountType;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index($type)
    {
        // Validate the type
        $validTypes = ['deposit', 'withdrawal', 'transfer', 'rebate', 'incentive'];
        if (!in_array($type, $validTypes)) {
            abort(404); // Handle invalid type
        }

        // Handle each type with a switch case
        switch ($type) {
            case 'deposit':
                return Inertia::render('Transaction/Deposit');
                
            case 'withdrawal':
                return Inertia::render('Transaction/Withdrawal');
                
            case 'transfer':
                return Inertia::render('Transaction/Transfer');
                
            case 'rebate':
                $accountTypes = AccountType::all()->map(function($accountType) {
                    return [
                        'value' => $accountType->id,
                        'name' => $accountType->name,
                    ];
                });
                return Inertia::render('Transaction/RebatePayout', ['accountTypes' => $accountTypes]);
                
            case 'incentive':
                return Inertia::render('Transaction/IncentivePayout');
                
            default:
                abort(404); // Fallback in case of invalid type
        }
    }
}
