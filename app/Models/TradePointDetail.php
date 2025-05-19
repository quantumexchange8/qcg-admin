<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradePointDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'symbol_group_id',
        'trade_point_rate',
    ];

    public function symbolGroup(): belongsTo
    {
        return $this->belongsTo(SymbolGroup::class, 'symbol_group_id', 'id');
    }
}
