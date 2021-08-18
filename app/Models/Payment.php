<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];
    
    public function order()
    {
       return $this->hasOne('App\Models\Order');
    }

    public function subscription()
    {
       return $this->hasOne('App\Models\Subscription');
    }
}