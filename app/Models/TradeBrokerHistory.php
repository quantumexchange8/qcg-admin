<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeBrokerHistory extends Model
{
    use HasFactory;

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'meta_login', 'meta_login');
    }
}
