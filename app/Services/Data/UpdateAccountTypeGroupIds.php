<?php

namespace App\Services\Data;

use App\Models\AccountType;
use Illuminate\Support\Facades\DB;

class UpdateAccountTypeGroupIds
{
    public function execute($data): void
    {
        // Directly update the account types using the provided data
        $this->updateAccountTypes($data);
    }

    /**
     * Update account types based on the provided data.
     */
    public function updateAccountTypes($data): void
    {
        foreach ($data['traderGroup'] as $group) {  // Access the traderGroup array
            $accountType = AccountType::query()
                ->where('account_group', $group['name'])
                ->first();

            if ($accountType) {
                DB::transaction(function () use ($accountType, $group) {
                    $accountType->account_group_id = $group['id'];  // Update with ID from $data
                    $accountType->save();
                });
            }
        }
    }
}
