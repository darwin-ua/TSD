<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model
{
    use SoftDeletes;

    protected $table = 'categories'; // Указываем, что модель связана с таблицей 'lesson'


    protected $dates = ['created_at', 'updated_at']; // Обработка дат
}
