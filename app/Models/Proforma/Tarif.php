<?php

namespace App\Models\Proforma;

use Illuminate\Database\Eloquent\Model;

class Tarif extends Model
{
    protected $table = 'tarifs';
    protected $guarded = ["id"];
    
    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'prscod',
        'prslib', 
        'prspun'
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'prspun' => 'decimal:2',
    ];
}