<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = ['event_id', 'user_id', 'hash']; // Поля, которые разрешено массово заполнять

    // Связь с моделью User (пользователь, который поставил лайк)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Связь с моделью Event (событие, которому поставили лайк)
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
