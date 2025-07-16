<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doing extends Model
{
    protected $fillable = [
        'event_id', 'session_id', 'discounte', 'user_id', 'uuid', 'name', 'description', 'price'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}

