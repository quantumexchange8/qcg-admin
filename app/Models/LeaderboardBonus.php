<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
