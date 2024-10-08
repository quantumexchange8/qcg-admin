<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTypeSymbolGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_type',
        'symbol_group',
        'amount',
    ];

    public function symbol_group(): belongsTo
    {
        return $this->belongsTo(SymbolGroup::class, 'symbol_group', 'id');
    }
}
