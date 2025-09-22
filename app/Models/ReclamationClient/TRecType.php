<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TRecType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_rec_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tticket_id',
        'b_rec_type_id',
        'libelle',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the details for this type.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TRecDetail::class, 't_rec_type_id');
    }

    /**
     * Get the ticket that owns this type.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TRecTicket::class, 'tticket_id', 'id');
    }

    /**
     * Get the base rec type that this type belongs to.
     */
    public function bRecType(): BelongsTo
    {
        return $this->belongsTo(BRecType::class, 'b_rec_type_id');
    }
}