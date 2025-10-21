<?php

namespace App\Models\ReclamationClient;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TRecNotification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 't_rec_notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tticket_id',
        'sender_id',
        'direction',
        'message',
        'type',
        'mode',
        'meta',
        'is_read'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean'
    ];

    /**
     * Get the ticket that owns the notification.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TRecTicket::class, 'tticket_id');
    }

    /**
     * Get the user who sent the notification.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
