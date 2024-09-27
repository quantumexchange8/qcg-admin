<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'sales_calculation_mode',
        'sales_category',
        'target_amount',
        'incentive_rate',
        'calculation_threshold',
        'calculation_period',
        'last_payout_date',
        'next_payout_date',
        'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'last_payout_date' => 'datetime',
            'next_payout_date' => 'datetime',
        ];
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function leaderboard_bonus(): HasMany
    {
        return $this->hasMany(LeaderboardBonus::class, 'leaderboard_profile_id', 'id');
    }

}
