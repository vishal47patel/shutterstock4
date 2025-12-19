<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Image extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'image',
        'title',
        'tags',
        'price',
        'desc',
        'category_id',
        'status',
        'alt',
        'user_id'
    ];
}
