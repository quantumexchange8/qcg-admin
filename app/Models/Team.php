<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'fee_charges',
        'color',
        'team_leader_id',
        'edited_by',
    ];

    // Relations
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id', 'id');
    }

    public function team_has_user(): HasMany
    {
        return $this->hasMany(TeamHasUser::class, 'team_id', 'id');
    }
}
