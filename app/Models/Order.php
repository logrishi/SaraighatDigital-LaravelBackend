<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];
    
    public function user()
    {
       return $this->belongsTo('App\User');
    }

    public function orderItems()
    {
       return $this->hasMany('App\Models\OrderItems');
    }

    public function payment(){
        return $this->belongsTo('App\Models\Payment');
    }

     public function getCreatedAtAttribute($value)
    {
        $timestamp = strtotime($value);
        $modifiedDate = date("d-m-Y", $timestamp);
        return $modifiedDate;
    }
}