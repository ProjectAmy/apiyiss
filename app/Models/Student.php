<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'walimurid_profile_id',
        'fullname',
        'nis',
        'unit',
        'grade',
    ];

    public function walimuridProfile()
    {
        return $this->belongsTo(WalimuridProfile::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
