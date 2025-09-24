<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonFile extends Model
{
    protected $table = 'lesson_files';

    protected $fillable = [
        'user_id',
        'events_id',
        'lesson_chapter',
        'text',
    ];

    public $timestamps = true;

    public function event()
    {
        return $this->belongsTo(Event::class, 'events_id');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_chapter');
    }



}

