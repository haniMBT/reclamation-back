<?php

namespace App\Models\ReclamationClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TRecFicherMessage extends Model
{
    use HasFactory;

    protected $table = 't_rec_ficher_message';

    protected $fillable = [
        'message_id',
        'nom_fichier',
        'nom_fichier_stocke',
        'chemin_fichier',
        'taille_fichier',
        'type_mime',
        'date_upload'
    ];

    protected $casts = [
        'date_upload' => 'datetime',
        'taille_fichier' => 'integer'
    ];

    /**
     * Relation avec le message
     */
    public function message()
    {
        return $this->belongsTo(TRecMessage::class, 'message_id');
    }

    /**
     * Obtenir l'URL de téléchargement du fichier
     */
    public function getDownloadUrlAttribute()
    {
        return route('messages.download-attachment', $this->id);
    }

    /**
     * Obtenir l'URL publique du fichier
     */
    public function getPublicUrlAttribute()
    {
        return Storage::disk('public')->url($this->chemin_fichier);
    }

    /**
     * Vérifier si le fichier existe sur le disque
     */
    public function fileExists()
    {
        return Storage::disk('public')->exists($this->chemin_fichier);
    }

    /**
     * Obtenir la taille formatée du fichier
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->taille_fichier;
        if ($bytes === 0) {
            return '0 Bytes';
        }
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Obtenir l'icône du fichier selon son type
     */
    public function getFileIconAttribute()
    {
        $mimeType = $this->type_mime;
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif ($mimeType === 'application/pdf') {
            return 'picture_as_pdf';
        } elseif (strpos($mimeType, 'word') !== false || strpos($mimeType, 'document') !== false) {
            return 'description';
        } elseif (strpos($mimeType, 'text/') === 0) {
            return 'text_snippet';
        } else {
            return 'attach_file';
        }
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


    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($fichier) {
            if ($fichier->fileExists()) {
                Storage::disk('public')->delete($fichier->chemin_fichier);
            }
        });
    }
}
