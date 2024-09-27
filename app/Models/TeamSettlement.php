<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamSettlement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'transaction_start_at',
        'transaction_end_at',
        'team_deposit',
        'team_withdrawal',
        'team_fee_percentage',
        'team_fee',
        'team_balance',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_start_at' => 'datetime',
            'transaction_end_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    // Relations
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
