<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TRecDestinataireMessage extends Model
{
    use HasFactory;

    protected $table = 't_rec_destinataires_messages';

    protected $fillable = [
        'message_id',
        'direction_destinataire_recepteur',
        'statut'
    ];

    /**
     * Relation avec le message
     */
    public function message()
    {
        return $this->belongsTo(TRecMessage::class, 'message_id');
    }

    /**
     * Relation avec la direction destinataire
     */
    public function directionDestinataire()
    {
        return $this->belongsTo(\App\Models\Direction::class, 'direction_destinataire_recepteur', 'NUMDIR');
    }

    /**
     * Scope pour les messages non lus
     */
    public function scopeNonLus($query)
    {
        return $query->where('statut', 'non_lu');
    }

    /**
     * Scope pour les messages lus
     */
    public function scopeLus($query)
    {
        return $query->where('statut', 'lu');
    }

    /**
     * Scope pour une direction spécifique
     */
    public function scopePourDirection($query, $directionId)
    {
        return $query->where('direction_destinataire_recepteur', $directionId);
    }
}