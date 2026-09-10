<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boite extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
        'annee',
        'capacite',
        'casier_id',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function casier()
    {
        return $this->belongsTo(Casier::class);
    }

    public function dossiers()
    {
        return $this->hasMany(Dossier::class);
    }



}



