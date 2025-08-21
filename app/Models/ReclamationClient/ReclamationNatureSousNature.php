<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Nature;
use App\Models\SousNature;

class ReclamationNatureSousNature extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 't_rec_nature_sous_nature';

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'reclamation_id',
        'nature_id',
        'sous_nature_id',
        'nature_lib',
        'sous_nature_lib'
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation : Une association appartient à une réclamation.
     */
    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class, 'reclamation_id');
    }

    /**
     * Relation : Une association appartient à une nature.
     */
    public function nature(): BelongsTo
    {
        return $this->belongsTo(Nature::class, 'nature_id', 'NATID');
    }

    /**
     * Relation : Une association peut appartenir à une sous-nature.
     */
    public function sousNature(): BelongsTo
    {
        return $this->belongsTo(SousNature::class, 'sous_nature_id', 'SOUSID');
    }
}
