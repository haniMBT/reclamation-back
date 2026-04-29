<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    use HasFactory;

    protected $table = 'password_reset_codes';
    
    protected $fillable = [
        'email',
        'code',
        'created_at'
    ];

    public $timestamps = false;

    protected $dates = [
        'created_at'
    ];
}