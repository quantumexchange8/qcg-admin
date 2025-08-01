<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    // use HasFactory, Notifiable, InteractsWithMedia, SoftDeletes, HasRoles, LogsActivity;
    use HasFactory, Notifiable, InteractsWithMedia, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function setReferralId(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = 'MOS';

        $length = 10 - strlen($randomString);

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $this->referral_code = $randomString;
        $this->save();
    }

    public function getChildrenIds(): array
    {
        return User::query()->where('hierarchyList', 'like', '%-' . $this->id . '-%')
            ->pluck('id')
            ->toArray();
    }

    public function assignedTeam($team_id): void
    {
        TeamHasUser::updateOrCreate(
            ['user_id' => $this->id],
            ['team_id' => $team_id]
        );
    }

    // Relations
    public function teamHasUser(): HasOne
    {
        return $this->hasOne(TeamHasUser::class, 'user_id');
    }

    public function directChildren(): HasMany
    {
        return $this->hasMany(User::class, 'upline_id', 'id');
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_id', 'id');
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'user_id', 'id');
    }

    public function rebate_wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id')->where('type', 'rebate_wallet');
    }

    public function incentive_wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id')->where('type', 'incentive_wallet');
    }

    public function trade_points(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id')->where('type', 'trade_points');
    }

    public function tradingAccounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class, 'user_id', 'id');
    }

    public function tradingUsers(): HasMany
    {
        return $this->hasMany(TradingUser::class, 'user_id', 'id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }

    public function rebateAllocations(): HasMany
    {
        return $this->hasMany(RebateAllocation::class, 'user_id', 'id');
    }

    public function interactions()
    {
        return $this->hasMany(UserPostInteraction::class, 'user_id'); 
    }

    public function read_ticket()
    {
        return $this->hasMany(TicketLog::class, 'user_id');
    }

    // // Logs
    // public function getActivitylogOptions(): LogOptions
    // {
    //     $user = $this->fresh();

    //     return LogOptions::defaults()
    //         ->useLogName('user')
    //         ->logOnly([
    //             'id',
    //             'name',
    //             'username',
    //             'email_verified_at',
    //             'email',
    //             'password',
    //             'dial_code',
    //             'phone',
    //             'phone_number',
    //             'dob',
    //             'address',
    //             'city_id',
    //             'state_id',
    //             'country_id',
    //             'nationality',
    //             'register_ip',
    //             'last_login_ip',
    //             'upline_id',
    //             'hierarchyList',
    //             'referral_code',
    //             'id_number',
    //             'kyc_status',
    //             'kyc_approved_at',
    //             'kyc_approval_description',
    //             'gender',
    //             'ct_user_id',
    //             'role',
    //             'status',
    //             'remarks',
    //             'rebate_amount',
    //             'remember_token',
    //         ])
    //         ->setDescriptionForEvent(function (string $eventName) use ($user) {
    //             $actorName = Auth::user() ? Auth::user()->name : 'System';
    //             return "$actorName has $eventName user with ID: $user->id";
    //         })
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs();
    // }
}
