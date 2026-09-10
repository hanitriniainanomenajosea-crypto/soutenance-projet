<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Les champs autorisés à être remplis lors de la création d'un utilisateur.
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'service_id',
    ];

    /**
     * Les champs masqués lors des retours d'API.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les conversions automatiques de types.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation : Un utilisateur (agent) appartient à un Service.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}