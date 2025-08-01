<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $guarded = [];
    
    public function rewards(): HasMany
    {
        return $this->hasMany(CompetitionReward::class, 'competition_id', 'id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'competition_id', 'id');
    }
}
