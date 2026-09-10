<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dossier extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_reference',
        'titre',
        'description',
        'statut',
        'boite_id',
        'user_id',
    ];

    /**
     * Accéder au service via la Boîte et le Casier
     */
    public function service()
    {
        return $this->hasOneThrough(
            Service::class,
            Casier::class,
            'id',          // Clé étrangère sur la table casiers
            'id',          // Clé étrangère sur la table services
            'boite_id',    // Clé locale sur la table dossiers
            'service_id'   // Clé locale sur la table casiers
        );
    }

    public function boite()
    {
        return $this->belongsTo(Boite::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id'); // Note : remplacez 'created_by' par 'user_id' si le champ s'appelle user_id
    }
}