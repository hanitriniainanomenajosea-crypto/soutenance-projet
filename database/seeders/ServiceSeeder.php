<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'nom' => 'Service État Civil',
                'code' => 'etat_civil',
                'abbreviation' => 'EC',
                'couleur_theme' => 'blue',
            ],
            [
                'nom' => 'Service Urbanisme et Foncier',
                'code' => 'urbanisme',
                'abbreviation' => 'URB',
                'couleur_theme' => 'emerald',
            ],
            [
                'nom' => 'Service Finances et Comptabilité',
                'code' => 'finances',
                'abbreviation' => 'FC',
                'couleur_theme' => 'amber',
            ],
            [
                'nom' => 'Service Ressources Humaines',
                'code' => 'rh',
                'abbreviation' => 'RH',
                'couleur_theme' => 'purple',
            ],

        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
