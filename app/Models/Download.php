<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Download extends Model
{
    protected $fillable = [
        'image_id',
        'user_id'
    ];
}
