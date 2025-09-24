<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'data_create_order', 'phone'];

    protected $fillable = [
        // ваші поля
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'order_id', 'id');
       // return $this->hasMany(Event::class, 'id', 'order_id');
    }
}


