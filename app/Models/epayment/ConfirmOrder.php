<?php

namespace App\Models\epayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class ConfirmOrder extends Model
{
    use HasFactory;

    protected $table = 'confirm_orders';

    protected $fillable = [
        'facnum',
        'facrfe',
        'domcod',
        'user_id',
        'trscod',
        'orderid',
        'expiration',
        'cardholderName',
        'depositAmount',
        'currency',
        'approvalCode',
        'authCode',
        'actionCode',
        'actionCodeDescription',
        'ErrorCode',
        'ErrorMessage',
        'OrderStatus',
        'OrderNumber',
        'Pan',
        'Ip',
        'SvfeResponse',
        'Amount',
        'transferred',
        'recuId'
    ];

    protected $casts = [
        'depositAmount' => 'decimal:2',
        'Amount' => 'decimal:2',
        'transferred' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
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

    // Relation avec la commande électronique
    public function eOrder()
    {
        return $this->belongsTo(EOrder::class, 'OrderNumber', 'id');
    }

    // Scope pour les commandes non transférées
    public function scopeUntransferred($query)
    {
        return $query->where('transferred', false);
    }

    // Scope pour les commandes réussies
    public function scopeSuccessful($query)
    {
        return $query->where('OrderStatus', 2)->where('ErrorCode', 0);
    }
}