<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

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

    protected function statusByDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $now = Carbon::now();

                if ($now->isBetween($this->start_date, $this->end_date, true)) {
                    return 'ongoing';
                }

                if ($now->lessThan($this->start_date)) {
                    return 'upcoming';
                }

                return 'completed';
            }
        );
    }
}
