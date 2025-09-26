<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanCode extends Model
{
    use HasFactory;

    protected $table = 'scan_code';

    // перечисляем только реально существующие поля
    protected $fillable = [
        'user_register',
        'document_id',
        'warehouse_id',
        'user_id',
        'cell',
        'code',
        'order_date',
        'amount',
        'status',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];
}

