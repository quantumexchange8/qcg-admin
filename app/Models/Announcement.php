<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Announcement extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;
    
    protected $fillable = [
        'title',
        'content',
        'start_date',
        'end_date',
        'recipient',
        'status',
        'popup',
        'popup_login',
        'pinned',
    ];
}
