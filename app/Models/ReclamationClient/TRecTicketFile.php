<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TRecTicketFile extends Model
{
    use HasFactory;

    protected $table = 't_rec_ticket_files';

    protected $fillable = [
        'ticket_id',
        'libelle',
        'nom_fichier',
        'chemin_fichier',
        'taille_fichier',
        'type_fichier',
        'mode'
    ];

    /**
     * Relation avec le ticket
     */
    public function ticket()
    {
        return $this->belongsTo(TRecTicket::class, 'ticket_id');
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