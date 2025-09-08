<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class BRecDetail extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 'b_rec_detail';

    const STATUTS = ['consultation', 'traitement'];

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'id_btype',
        'libelle',
        'direction',
        'statut_direction',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation : Un détail appartient à un type.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(BRecType::class, 'id_btype');
    }

    /**
     * Règles de validation
     */
    public static function validationRules(): array
    {
        return [
            'id_btype' => 'required|exists:b_rec_type,id',
            'libelle' => 'required|string|max:255',
            'direction' => 'nullable|string|max:255',
            'statut_direction' => ['nullable', Rule::in(self::STATUTS)],
        ];
    }
}