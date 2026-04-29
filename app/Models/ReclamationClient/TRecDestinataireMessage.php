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
        'direction_destinataire',
        'statut',
        'lu',
        'date_lecture'
    ];

    protected $casts = [
        'date_lecture' => 'datetime',
        'lu' => 'boolean'
    ];

    /**
     * Relation avec le message
     */
    public function message()
    {
        return $this->belongsTo(TRecMessage::class, 'message_id');
    }

    // direction_destinataire est maintenant un champ text, pas de relation

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

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
