<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRecInfoGeneral extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_rec_info_general';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tticket_id',
        'info_general_id',
        'libelle',
        'key_attribut',
        'value',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'key_attribut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the ticket that owns this info general.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TRecTicket::class, 'tticket_id', 'id');
    }

    /**
     * Get the base info general that this info belongs to.
     */
    public function baseInfoGeneral(): BelongsTo
    {
        return $this->belongsTo(BRecInfoGeneral::class, 'info_general_id', 'id');
    }
}