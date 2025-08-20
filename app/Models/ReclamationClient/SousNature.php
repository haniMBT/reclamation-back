<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousNature extends Model
{
    use HasFactory;

    protected $table = 'sous_nature';
    protected $primaryKey = 'SOUSID';
    public $timestamps = false;

    protected $fillable = [
        'SOUSLIB',
        'NATID',
    ];

    /**
     * Relation avec la nature parente
     */
    public function nature()
    {
        return $this->belongsTo(Nature::class, 'NATID');
    }
}