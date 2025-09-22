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
        'nom_fichier',
        'chemin_fichier',
        'taille_fichier',
        'type_fichier'
    ];

    /**
     * Relation avec le ticket
     */
    public function ticket()
    {
        return $this->belongsTo(TRecTicket::class, 'ticket_id');
    }
}