<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 'direction';

    /**
     * La clé primaire de la table.
     */
    protected $primaryKey = 'NUMDIR';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     */
    public $incrementing = true;

    /**
     * Le type de la clé primaire.
     */
    protected $keyType = 'int';

    /**
     * Indique si le modèle doit gérer les timestamps.
     */
    public $timestamps = false;

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'DIRECTION',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'NUMDIR' => 'integer',
        'DIRECTION' => 'string',
    ];

    /**
     * Relation : Une direction peut avoir plusieurs utilisateurs.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'direction', 'DIRECTION');
    }

    /**
     * Règles de validation
     */
    public static function validationRules(): array
    {
        return [
            'DIRECTION' => 'required|string|unique:direction,DIRECTION',
        ];
    }

    /**
     * Règles de validation pour la mise à jour
     */
    public static function validationRulesForUpdate($id): array
    {
        return [
            'DIRECTION' => 'required|string|unique:direction,DIRECTION,' . $id . ',NUMDIR',
        ];
    }
}
