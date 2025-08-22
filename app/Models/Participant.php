<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class, 'competition_id', 'id');
    }

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class, 'meta_login', 'meta_login');
    }
}