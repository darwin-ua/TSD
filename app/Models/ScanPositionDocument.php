<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScanPositionDocument extends Model
{
    protected $table = 'scan_position_document';

    protected $fillable = [
        'user_register',
        'document_id',
        'warehouse_id',
        'position_name',
        'number_position',
        'quantity',
        'cell',
        'code',
        'amount',
        'status',
        'id_ssylka',   // 👈 ДОБАВЬ ЭТО
    ];
}
