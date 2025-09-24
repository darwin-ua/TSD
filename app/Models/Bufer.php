<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bufer extends Model
{
    protected $table = 'bufer'; // 👈 Указываем реальное имя таблицы
    protected $fillable = ['text'];
}


