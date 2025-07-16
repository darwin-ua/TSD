<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App;

class Subcategory extends Model
{
    use SoftDeletes;

    protected $table = 'categories_sub';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $locale = App::getLocale();

        $this->setTable($this->getTable().'_'.$locale);
    }

}
