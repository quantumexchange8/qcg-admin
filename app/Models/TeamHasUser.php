<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeamHasUser extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'user_id',
    ];

    // Relations
    public function team(): belongsTo
    {
        return $this->belongsTo(Team::class, 'team_id', 'id');
    }
}
