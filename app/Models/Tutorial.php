<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutorial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'is_published',
        'video_type'
    ];
}
