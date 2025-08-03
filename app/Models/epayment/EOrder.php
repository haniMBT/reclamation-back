<?php

namespace App\Models\epayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class EOrder extends Model
{
    use HasFactory;

    protected $table = 'e_orders';

    protected $fillable = [
        'facnum',
        'domcod',
        'user_id',
        'payment_token',
        'payment_token_expires_at'
    ];

    protected $casts = [
        'payment_token_expires_at' => 'datetime'
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec la facture
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facnum', 'facnum');
    }

    // Vérifier si le token est expiré
    public function isTokenExpired()
    {
        return $this->payment_token_expires_at && now()->greaterThan($this->payment_token_expires_at);
    }
}