<?php

namespace App\Models\epayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FailedOrder extends Model
{
    use HasFactory;

    protected $table = 'failed_orders';

    protected $fillable = [
        'facnum',
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
        'Amount'
    ];

    protected $casts = [
        'depositAmount' => 'decimal:2',
        'Amount' => 'decimal:2'
    ];

    // Relation avec la facture
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facnum', 'facnum');
    }
}