<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'reward_id',
        'receiving_account',
        'recipient_name',
        'dial_code',
        'phone',
        'phone_number',
        'address',
        'status',
        'remarks',
        'approved_at',
        'handle_by',
    ];

    public function reward(): belongsTo
    {
        return $this->belongsTo(Reward::class, 'reward_id', 'id');
    }
}
