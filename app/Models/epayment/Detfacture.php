<?php

namespace App\Models\epayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Detfacture extends Model
{
    use HasFactory;

    protected $fillable = [
        'facnum',
        'prscod',
        'prslib',
        'dfaqte',
        'dfadur', 
        'dfapun',
        'dfamnt',
        // Anciens champs pour compatibilité
        'artcod',
        'artnom',
        'artqte',
        'artprix',
        'arttva',
        'artttc'
    ];

    protected $casts = [
        'dfaqte' => 'decimal:3',
        'dfapun' => 'decimal:4',
        'dfamnt' => 'decimal:2',
        // Anciens champs pour compatibilité
        'artqte' => 'decimal:3',
        'artprix' => 'decimal:2',
        'arttva' => 'decimal:2',
        'artttc' => 'decimal:2'
    ];

    // Relation avec la facture
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facnum', 'facnum');
    }
}