<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Permissions\HasPermissionsTrait;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Scopes\SuperAdminScope;

class User extends Authenticatable implements Auditable,MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, AuditableTrait, HasPermissionsTrait, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'referrer_id',
        'name',
        'email',
        'password',
        'referral_token',
        'reference',
        'package_id',
        'level_id',
        'is_active',
        'is_epin_requested',
        'is_a_withdrawal_request_sent',
        'referral_code',
        'mobile',
        'address',
        'active_by',
        'referrer_package_id',
        'referrer_level_id',
        'parent_id',
        'package_at',
        'active_at',
        'otp',
        'otp_at',
        'profile',
    ];

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['referral_link','earned_rewards'];

    public static function boot()
    {
        parent::boot();

        if (\Auth::check() && \Auth::user()->hasRole('user','admin')) {
            static::addGlobalScope(new SuperAdminScope);
        }

        /*Generate unique codes*/
        static::creating(function ($user) {
            $user->refresh();
            $last_user_id = User::latest()->orderBy('id','DESC')->first();
            $current_prefix = config('services.prefix.user_reference');
            $ref_code = $current_prefix.str_pad(
                ($last_user_id->id??0)+1, 6, '0',
                STR_PAD_LEFT
            );
            $user->reference = $ref_code;
            $user->referral_token = $ref_code;
            $user->referral_code = $ref_code;
        });

        /*Assign a role to registered user*/
        static::created(function ($user) {
            if (!$user->hasRole('user','admin','superadmin')) {
                $user_role = Role::where('slug', 'user')->first();
                $user->roles()->detach();
                $user->roles()->attach($user_role);
            }
        });
    }

    public static function generateReferralCode()
    {
        $code = Str::random(6);
        if (self::where('referral_code',$code)->count() > 0) self::generateReferralCode();
        return $code;
    }

    public function epinRequest()
    {
        return $this->hasOne(Epin::class, 'user_id');
    }

    /**
     * A user has a referrer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id', 'id');
    }

    /**
     * A user has many referrals.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referrer_id', 'id');
    }

    /**
     * Get the user's referral link.
     *
     * @return string
     */
    public function getReferralLinkAttribute()
    {
        return $this->referral_link = route('register', ['ref' => $this->referral_token]);
    }

    public function scopeReferralToken($query,$token='')
    {
        return $query->where('referral_token',$token);
    }

    public function levelInformation()
    {
        return $this->belongsTo(Level::class, 'level_id', 'id');
    }

    public function packageInformation()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }

    /**
     * Get the user's total earned rewards
     *
     * @return string
     */
    public function getEarnedRewardsAttribute()
    {
        $user = auth()->user();
        if ($user) {
            $total_referrals = $user->referrals->count();
            $package = $user->packageInformation;
            
            $commission = Commission::query()
                ->where('level_id',$user->level_id)
                ->where('package_id',$user->package_id)
                ->latest()
                ->first();
            if (empty($commission)) {
                return 0;
            }
            $deductions = json_decode($commission->deductions);
            
            $main_wallet_deductions = (integer) $deductions->main;
            $upgrade_wallet_deductions = (integer) $deductions->upgrade;

            $main_wallet = $total_referrals * $upgrade_wallet_deductions;
            $upgrade_wallet = $total_referrals * $main_wallet_deductions; 

            $earnings = $main_wallet - $upgrade_wallet;

            return $earnings;
        }else{
            return 0;
        }
    }

    public function payments()
    {
        return $this->hasMany(Payments::class, 'user_id');
    }

    public function verificationPayments()
    {
        return $this->hasMany(Payments::class, 'user_id')->where('paid_for','verification');
    }

    public function epinPayments()
    {
        return $this->hasMany(Payments::class, 'user_id')->where('paid_for','epin');
    }

    /*Get my team level history*/
    public function levelIn()
    {
        return $this->belongsTo(Level::class, 'referrer_level_id', 'id');
    }

    public function packageIn()
    {
        return $this->belongsTo(Package::class, 'referrer_package_id', 'id');
    }
    /*Get my team level history*/

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }
    
    public function child()
    {
        return $this->hasMany(User::class, 'parent_id', 'id')->with('child');
    }
    
    public function bank()
    {
        return $this->hasOne(BankDetail::class, 'user_id', 'id');
    }
    
    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id', 'id');
    }
    
    public function activeReq()
    {
        return $this->hasOne(Payments::class, 'user_id', 'id')->where('paid_for', 'verification')->first();
    }
}