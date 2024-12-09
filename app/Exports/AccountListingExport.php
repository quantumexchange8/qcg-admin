<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class AccountListingExport implements FromCollection, WithHeadings
{
    protected $accounts;

    public function __construct($accounts)
    {
        $this->accounts = $accounts;
    }

    public function collection()
    {
        // Fetch accounts
        $accounts = $this->accounts->select([
            'users.first_name as name',
            'users.email',
            'trading_users.meta_login as account',
            'trading_accounts.balance as balance',
            'trading_accounts.equity as equity',
            'trading_accounts.credit as credit',
            'trading_users.created_at as joined_date',
        ])->get();
    
        // Use foreach to replace null values with 0
        foreach ($accounts as $account) {
            $account->balance = $account->balance ? $account->balance : '0';  // Replace null balance with 0
            $account->equity = $account->equity ? $account->balance : '0';    // Replace null equity with 0
            $account->credit = $account->credit ? $account->balance : '0';    // Replace null credit with 0
        }

        return $accounts;
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Account', 'Balance', 'Equity', 'Credit', 'Joined Date'];
    }
}
