<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TRecTicket extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_rec_tickets';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bticket_id',
        'user_id',
        'direction',
        'status',
        'objet',
        'description',
        'nom',
        'prenom',
        'is_creator_validated',
        'date_validation_createur',
        'closed_at',
        'closed_by',
        'conclusion',
        'motif_changement',
        'reply_permission',
        'date_en_cours',
        'date_recours',
        'date_cloture_recours',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'closed_at' => 'datetime',
        'date_validation_createur' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'date_en_cours' => 'datetime',
        'date_recours' => 'datetime',
        'date_cloture_recours' => 'datetime',
    ];

    /**
     * Get the types for this ticket.
     */
    public function types(): HasMany
    {
        return $this->hasMany(TRecType::class, 'tticket_id', 'id');
    }

    /**
     * Get the informations générales for this ticket.
     */
    public function infosGenerales(): HasMany
    {
        return $this->hasMany(TRecInfoGeneral::class, 'tticket_id', 'id');
    }

    /**
     * Get the info generals for this ticket.
     */
    public function infos(): HasMany
    {
        return $this->hasMany(TRecInfoGeneral::class, 'tticket_id', 'id');
    }

    /**
     * Get the user that owns this ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the base ticket that this ticket belongs to.
     */
    public function baseTicket(): BelongsTo
    {
        return $this->belongsTo(BRecTickets::class, 'bticket_id');
    }

    /**
      * Relation avec les fichiers du ticket
      */
     public function files()
     {
         return $this->hasMany(TRecTicketFile::class, 'ticket_id');
     }
}