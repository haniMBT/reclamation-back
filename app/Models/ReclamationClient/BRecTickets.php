<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BRecTickets extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 'b_rec_tickets';

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'libelle',
        'documentAfornir',
        'direction',
        'definition',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation : Un ticket peut avoir plusieurs types.
     */
    public function types(): HasMany
    {
        return $this->hasMany(BRecType::class, 'id_btickes');
    }

    /**
     * Relation : Un ticket peut avoir plusieurs informations générales.
     */
    public function infosGenerales(): HasMany
    {
        return $this->hasMany(BRecInfoGeneral::class, 'bticket_id');
    }
}