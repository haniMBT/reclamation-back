<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Reclamation extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 't_rec_reclamation';

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'objet',
        'contenu',
        'user_id',
        'statut',
        'date_creation',
        'date_traitement',
        'reponse',
        'traite_par',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'date_creation' => 'datetime',
        'date_traitement' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les statuts autorisés pour les réclamations.
     */
    public const STATUTS = [
        'nouvelle' => 'Nouvelle',
        'en_cours' => 'En cours',
        'traitee' => 'Traitée',
        'fermee' => 'Fermée',
    ];

    /**
     * Relation : Une réclamation appartient à un utilisateur (créateur).
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation : Une réclamation appartient à un utilisateur (celui qui l'a traitée).
     */
    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    /**
     * Relation : Une réclamation peut avoir plusieurs fichiers joints.
     */
    public function fichiers(): HasMany
    {
        return $this->hasMany(\App\Models\ReclamationClient\FichierClient::class, 'reclamation_id');
    }

    /**
     * Scope : Réclamations par statut.
     */
    public function scopeParStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope : Réclamations récentes.
     */
    public function scopeRecentes($query, $jours = 30)
    {
        return $query->where('date_creation', '>=', now()->subDays($jours));
    }

    /**
     * Accesseur : Formater le statut pour l'affichage.
     */
    public function getStatutFormatteAttribute()
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /**
     * Accesseur : Vérifier si la réclamation est nouvelle.
     */
    public function getEstNouvelleAttribute()
    {
        return $this->statut === 'nouvelle';
    }

    /**
     * Accesseur : Vérifier si la réclamation est fermée.
     */
    public function getEstFermeeAttribute()
    {
        return $this->statut === 'fermee';
    }
}
