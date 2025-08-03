<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FichierClient extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     */
    protected $table = 't_rec_fichiers_client';

    /**
     * Les attributs qui peuvent être assignés en masse.
     */
    protected $fillable = [
        'reclamation_id',
        'nom_original',
        'nom_stockage',
        'chemin',
        'taille',
        'type_mime',
        'date_upload',
    ];

    /**
     * Les attributs qui doivent être mutés en dates.
     */
    protected $casts = [
        'date_upload' => 'datetime',
        'taille' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation : Un fichier appartient à une réclamation.
     */
    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(Reclamation::class, 'reclamation_id');
    }

    /**
     * Accesseur : Obtenir l'URL publique du fichier.
     */
    public function getUrlAttribute()
    {
        return Storage::url(str_replace('public/', '', $this->chemin));
    }

    /**
     * Accesseur : Formater la taille du fichier pour l'affichage.
     */
    public function getTailleFormateeAttribute()
    {
        $bytes = $this->taille;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' octets';
        } elseif ($bytes == 1) {
            return $bytes . ' octet';
        } else {
            return '0 octet';
        }
    }

    /**
     * Accesseur : Vérifier si le fichier est une image.
     */
    public function getEstImageAttribute()
    {
        return str_starts_with($this->type_mime, 'image/');
    }

    /**
     * Accesseur : Vérifier si le fichier est un PDF.
     */
    public function getEstPdfAttribute()
    {
        return $this->type_mime === 'application/pdf';
    }

    /**
     * Accesseur : Obtenir l'extension du fichier.
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->nom_original, PATHINFO_EXTENSION);
    }

    /**
     * Supprimer le fichier physique avant de supprimer l'enregistrement.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($fichier) {
            if (Storage::exists($fichier->chemin)) {
                Storage::delete($fichier->chemin);
            }
        });
    }
}
