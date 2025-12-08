<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaliMurid extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];
}
