<?php

namespace App\Models\Proforma;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Historique extends Model
{
    // Si votre migration active s'appelle proforma_historiques, décommentez la ligne ci-dessous
    // protected $table = 'proforma_historiques';
    protected $table = 'historiques';
    protected $guarded = ["id"];
    
    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'cnsbld',
        'dctcod',
        'scan',
        'date_fin',
        'ttc',
        'user_id'
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'scan' => 'boolean',
        'date_fin' => 'date',
        'ttc' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}