<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TRecMessage extends Model
{
    use HasFactory;

    protected $table = 't_rec_message';

    protected $fillable = [
        'tticket_id',
        'titre',
        'texte',
        'direction_envoi',
        'sender_id',
        'date_envoie'
    ];

    protected $casts = [
        'date_envoie' => 'datetime'
    ];

    /**
     * Relation avec le ticket
     */
    public function ticket()
    {
        return $this->belongsTo(TRecTicket::class, 'tticket_id');
    }

    /**
     * Relation avec l'utilisateur expéditeur
     */
    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    /**
     * Relation avec les destinataires
     */
    public function destinataires()
    {
        return $this->hasMany(TRecDestinataireMessage::class, 'message_id');
    }

    /**
     * Relation avec les fichiers joints
     */
    public function fichiers()
    {
        return $this->hasMany(TRecFicherMessage::class, 'message_id');
    }

    /**
     * Relation avec la direction d'envoi
     */
    public function directionEnvoi()
    {
        return $this->belongsTo(\App\Models\Direction::class, 'direction_envoi', 'NUMDIR');
    }
}