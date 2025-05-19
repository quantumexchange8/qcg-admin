<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradePointPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'period_name',
        'start_date',
        'end_date',
        'status',
    ];
}
