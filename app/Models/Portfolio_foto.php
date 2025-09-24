<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioFoto extends Model
{
    use HasFactory;

    protected $table = 'portfolio_foto';

    protected $guarded = ['id', 'created_at', 'updated_at'];
}
