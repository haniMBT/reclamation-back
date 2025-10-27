<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BRecInfoGeneral extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 'b_rec_info_general';

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'bticket_id',
        'libelle',
        'key_attirubut',
        'type',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'key_attirubut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation : Une information générale appartient à un ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(BRecTickets::class, 'bticket_id');
    }
}