<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlugRedirect extends Model
{
    protected $fillable = [
        'source_slug',
        'target_slug',
        'model_type',
        'model_id',
    ];
}
