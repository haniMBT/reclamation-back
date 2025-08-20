<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nature extends Model
{
    use HasFactory;

    protected $table = 'nature';
    protected $primaryKey = 'NATID';
    public $timestamps = false;

    protected $fillable = [
        'NATLIB',
        'ORDRE',
    ];

    /**
     * Relation avec les sous-natures
     */
    public function sousNatures()
    {
        return $this->hasMany(SousNature::class, 'NATID');
    }

    /**
     * Supprimer une nature avec toutes ses sous-natures
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($nature) {
            $nature->sousNatures()->delete();
        });
    }
}