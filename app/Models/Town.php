<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    protected $fillable = ['name', 'code', 'region_id'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}

