<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BRecTicketFile extends Model
{
    use HasFactory;

    protected $table = 'b_rec_ticket_files';

    protected $fillable = [
        'bticket_id',
        'libelle',
        'obligatoire',
        'format_fichier',
    ];

    protected $casts = [
        'obligatoire' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}