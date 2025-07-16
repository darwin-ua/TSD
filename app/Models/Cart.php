<?php
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'cart';

    protected $fillable = [
        'token',
        'product_id',
        'count',
        'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

