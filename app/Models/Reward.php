<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'type',
        'code',
        'name',
        'trade_point_required',
        'start_date',
        'expiry_date',
        'maximum_redemption',
        'autohide_after_expiry',
        'edited_by',
    ];

    protected $casts = [
        'trade_point_required' => 'float',
    ];

    public function redemption(): HasMany
    {
        return $this->hasMany(RewardRedemption::class, 'reward_id', 'id');
    }
}
