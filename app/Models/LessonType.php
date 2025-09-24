<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonType extends Model
{
    protected $table = 'lesson_type';

    protected $fillable = [
        'events_id',
        'type',
    ];

    public $timestamps = true;
}
