<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use SoftDeletes;

    protected $table = 'lesson'; // Указываем, что модель связана с таблицей 'lesson'

    protected $fillable = [
        'title',
        'foto_folder_id',
        'add_fields',
        'category',
        'description',
        'description_long',
        'terms',
        'user_id',
        'is_live',
        'is_links',
        'online',
        'webcams',
        'locale',
        'status',
        'allfoto',
    ];

    protected $dates = ['deleted_at', 'created_at', 'updated_at']; // Обработка дат
}


