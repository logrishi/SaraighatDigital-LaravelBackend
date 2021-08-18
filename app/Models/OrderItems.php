<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $guarded = [];
    
    public function order()
    {
       return $this->belongsTo('App\Models\Order');
    }
    public function product()
    {
       return $this->belongsTo('App\Models\Product');
    }
}