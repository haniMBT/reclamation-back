<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRecTicketDirection extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_rec_ticket_direction';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tticket_id',
        'direction',
        'statut_direction',
        'source_orientation',
        'type_orientation',
    ];

    /**
     * Get the ticket that owns the direction.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TRecTicket::class, 'ticket_id');
    }
}
