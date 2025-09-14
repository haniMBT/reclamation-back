<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    // protected $hidden = [
    //     'remember_token',
    // ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'Password' => 'hashed',
    ];

    public function scopePrivileges($volet_app)
    {
        $privilege = DB::table('p_privileges')->join('p_profils', 'p_profils.code', 'p_privileges.profil_code')
            ->where('p_profils.code', Auth::user()->privilege)->where('volet', $volet_app)
            ->select('p_privileges.*')->first();
        return $privilege;
    }


    public function hasExistingProfil() {
        return false;
    }

    /**
     * Relation : Un utilisateur appartient à une direction.
     */
    public function directionRelation(): BelongsTo
    {
        return $this->belongsTo(Direction::class, 'direction', 'DIRECTION');
    }

    public function receivesBroadcastNotificationsOn()
    {
        // return 'users.'.$this->id;
        return 'App.Models.User.'.$this->id;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
