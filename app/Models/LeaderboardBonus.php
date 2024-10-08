<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardBonus extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'leaderboard_profile_id',
        'target_amount',
        'achieved_amount',
        'achieved_percentage',
        'incentive_rate',
        'incentive_amount',
        'incentive_month',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function leaderboard_profile(): BelongsTo
    {
        return $this->belongsTo(LeaderboardProfile::class, 'leaderboard_profile_id', 'id');
    }

}
