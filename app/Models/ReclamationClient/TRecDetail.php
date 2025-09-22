<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRecDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_rec_detail';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        't_rec_type_id',
        'b_rec_detail_id',
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
     * Get the type that owns this detail.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TRecType::class, 't_rec_type_id');
    }

    /**
     * Get the base rec detail that this detail belongs to.
     */
    public function bRecDetail(): BelongsTo
    {
        return $this->belongsTo(BRecDetail::class, 'b_rec_detail_id');
    }
}