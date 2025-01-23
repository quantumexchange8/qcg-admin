<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccountTypeVisibility extends Model
{
    protected $fillable = [
        'account_type_id', 
        'user_id',
    ];

    // Example relationship with AccountType
    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    // Example relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
