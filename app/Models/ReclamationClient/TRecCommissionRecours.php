<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TRecCommissionRecours extends Model
{
    use HasFactory;

    protected $table = 't_rec_commission_recours';

    protected $fillable = [
        'user_id',
        'nom',
        'prenom',
        'email',
        'matricule',
        'direction',
        'role',
    ];

    /**
     * Relation: membre/president appartient à user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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