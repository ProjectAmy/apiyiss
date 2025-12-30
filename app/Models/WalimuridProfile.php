<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalimuridProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fullname',
        'shortname',
        'call_name',
        'phone',
        'address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
