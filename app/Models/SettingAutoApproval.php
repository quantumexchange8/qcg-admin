<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingAutoApproval extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'start_time',
        'end_time',
        'spread_amount',
        'status',
    ];
}
