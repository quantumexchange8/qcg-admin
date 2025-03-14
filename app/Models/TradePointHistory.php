<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradePointHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category',
        'symbol_group_id',
        'redemption_id',
        'trade_points',
        'remarks',
        'handle_by',
    ];

    public function redemption()
    {
        return $this->belongsTo(RewardRedemption::class, 'redemption_id');
    }

    public function symbolGroup(): belongsTo
    {
        return $this->belongsTo(SymbolGroup::class, 'symbol_group_id', 'id');
    }
}
