<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'code',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function casiers()
    {
        return $this->hasMany(Casier::class);
    }

    public function boites()
    {
        return $this->hasMany(Boite::class);
    }

    public function dossiers()
    {
        return $this->hasMany(Dossier::class);
    }
}