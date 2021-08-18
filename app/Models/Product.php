<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    public function orderItem(){
        return $this->hasOne('App\Models\OrderItems');
    }
    
    public function getForSaleAttribute($value)
    {
        if($value == 0){
            return "False";
        }else{
            return "True";
        }
    }
    public function getIsFreeAttribute($value)
    {
        if($value == 0){
            return "False";
        }else{
            return "True";
        }
    }
}