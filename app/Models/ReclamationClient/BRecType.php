<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class BRecType extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 'b_rec_type';

    const STATUTS = ['consultation', 'traitement'];

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'id_btickes',
        'libelle',
        'direction',
        'statut_direction',
        'position',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'direction' => 'array'
    ];

    /**
     * Relation : Un type appartient à un ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(BRecTickets::class, 'id_btickes');
    }

    /**
     * Relation : Un type peut avoir plusieurs détails.
     */
    public function details(): HasMany
    {
        return $this->hasMany(BRecDetail::class, 'id_btype');
    }

    /**
     * Règles de validation
     */
    public static function validationRules(): array
    {
        return [
            'id_btickes' => 'required|exists:b_rec_tickets,id',
            'libelle' => 'required|string',
            'direction' => 'nullable|array',
            'direction.*' => 'nullable|string',
            'statut_direction' => ['nullable', Rule::in(self::STATUTS)],
        ];
    }
}
