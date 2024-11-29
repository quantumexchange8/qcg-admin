<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'account_group_id',
        'account_group',
        'group',
        'category',
        'color',
        'minimum_deposit',
        'leverage',
        'currency',
        'allow_create_account',
        'maximum_account_number',
        'type',
        'commission_structure',
        'trade_open_duration',
        'account_group_balance',
        'account_group_equity',
        'descriptions',
        'edited_by',
    ];

    public function trading_accounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class, 'account_type', 'id');
    }

}
