<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Privilege extends Model
{
    use HasFactory;

    protected $table='p_privileges';

     protected $fillable = [
        'profil_code',
        'module',
        'volet',
        'description',
        'consultation',
        'modification',
        'insertion',
        'suppression',
        'visibilite',
        'role',
    ];

    protected $casts  = [
        'profil_code' => 'string',
        'consultation' => 'boolean',
        'insertion' => 'boolean',
        'modification' => 'boolean',
        'suppression' => 'boolean'
    ];

}
