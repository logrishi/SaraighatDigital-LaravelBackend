<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public function user()
    {
       return $this->belongsTo('App\User');
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
    
    public function getExpiresOnAttribute($value)
    {
        $timestamp = strtotime($value);
        $modifiedDate = date("d-m-Y", $timestamp);
        return $modifiedDate;
    }
}