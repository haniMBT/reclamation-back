<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoletApp extends Model
{
    use HasFactory;
    protected $table='p_volets_app';

       protected $primaryKey ="id";
    protected $fillable = [
        'volet',
        'module',
        'description'
    ];

    public function privileges()
    {
        return $this->belongsToMany(User::class, Privilege::class, 'volet_app', 'profil_code', 'volet', 'privilege');
    }
}
