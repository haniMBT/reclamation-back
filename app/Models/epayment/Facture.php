<?php

namespace App\Models\epayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'facnum',
        'facdat', 
        'trscod',
        'trsnom',
        'trsadr',
        'trsnrc',
        'trsnis',
        'trstel',
        'trseml',
        'facttc',
        'facmnt',
        'factva',
        'facfix',
        'facttv',
        'status',
        'domcod',
        'facrfe',
        'escnum',
        'navnom',
        'escdar',
        'cnsbld',
        'taxnum'
    ];

    protected $casts = [
        'facdat' => 'datetime',
        'escdar' => 'datetime',
        'facttc' => 'decimal:2',
        'facmnt' => 'decimal:2',
        'factva' => 'decimal:2',
        'facfix' => 'decimal:2',
        'facttv' => 'decimal:2',
        'status' => 'integer'
    ];

    // Relation avec les détails de facture
    public function details()
    {
        return $this->hasMany(Detfacture::class, 'facnum', 'facnum');
    }

    // Relation avec les commandes électroniques
    public function eOrders()
    {
        return $this->hasMany(EOrder::class, 'facnum', 'facnum');
    }

    // Relation avec les commandes confirmées
    public function confirmedOrders()
    {
        return $this->hasMany(ConfirmOrder::class, 'facnum', 'facnum');
    }

    // Relation avec les commandes échouées
    public function failedOrders()
    {
        return $this->hasMany(FailedOrder::class, 'facnum', 'facnum');
    }

    // Scope pour les factures non payées
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 1);
    }

    // Scope pour les factures payées
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    // Vérifier si la facture est payée
    public function isPaid()
    {
        return $this->status == 1;
    }
}