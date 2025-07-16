<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $dates = [
        'start_date', 'end_date' // Эти атрибуты будут обрабатываться как экземпляры Carbon
    ];
}
