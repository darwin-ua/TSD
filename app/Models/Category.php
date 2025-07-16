<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App; // Add this line to import the App class

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $locale = App::getLocale();

        $this->setTable($this->getTable().'_'.$locale);
    }
}
