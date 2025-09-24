<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioFoto extends Model
{
    use HasFactory;

    protected $table = 'portfolio_foto';

    protected $fillable = [
        'title',
        'event_id',
    ];

    // Метод для указания связи с моделью Event
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
