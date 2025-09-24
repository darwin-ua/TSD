<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UserData extends Model
{
    // Указание имени таблицы, если оно не соответствует стандартному названию Laravel
    protected $table = 'users_data';

    // Указание полей, которые можно массово назначать
    protected $fillable = ['user_id', 'email', 'settings'];

    // Если в вашей таблице нет полей created_at и updated_at
    public $timestamps = false;
}

