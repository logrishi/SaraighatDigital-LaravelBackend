<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Notifications\PasswordResetNotification;

class User extends Authenticatable
{

    public function isAdmin()
    {
        return $this->is_admin; // AuhAdmin middleware
    }

    public function orders()
    {
       return $this->hasMany('App\Models\Order');
    }

    public function subscription()
    {
       return $this->hasMany('App\Models\Subscription');
    }

    public function deviceTokens()
    {
       return $this->hasMany('App\Models\DeviceToken');
    }

    public function otps()
    {
       return $this->hasMany('App\Models\Otp');
    }

    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }
}