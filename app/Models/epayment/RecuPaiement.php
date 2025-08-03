<?php

namespace App\Models\epayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecuPaiement extends Model
{
    use HasFactory;

    protected $table = 'recu_paiements';

    protected $fillable = [
        'facnum',
        'orderid',
        'user_id',
        'montant',
        'date_paiement',
        'reference'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'datetime'
    ];

    // Relation avec la facture
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facnum', 'facnum');
    }

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}