<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profil extends Model
{
    use HasFactory;

    protected $table = 'p_profils';
    protected $primaryKey = 'code';
    public $incrementing = false;
    public $timestamps = false;

}
