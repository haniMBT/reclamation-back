<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BRecDefaultDirection extends Model
{
    use HasFactory;

    protected $table = 'b_rec_default_directions';

    protected $fillable = [
        'bticket_id',
        'direction',
        'statut_direction',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(BRecTickets::class, 'bticket_id');
    }
}