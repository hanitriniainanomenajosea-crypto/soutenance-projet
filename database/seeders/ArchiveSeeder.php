<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Casier;
use App\Models\Boite;

class ArchiveSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Définition des 4 services municipaux avec l'abréviation
        $services = [
            ['nom' => 'État Civil', 'code' => 'EC', 'abbreviation' => 'EC'],
            ['nom' => 'Urbanisme', 'code' => 'URB', 'abbreviation' => 'URB'],
            ['nom' => 'Finances & Comptabilité', 'code' => 'FIN', 'abbreviation' => 'FIN'],
            ['nom' => 'Ressources Humaines', 'code' => 'RH', 'abbreviation' => 'RH'],
        ];

        foreach ($services as $serviceData) {
            // Création ou récupération du service
            $service = Service::firstOrCreate(
                ['code' => $serviceData['code']],
                [
                    'nom' => $serviceData['nom'],
                    'abbreviation' => $serviceData['abbreviation'],
                ]
            );

            // 2. Création de 2 casiers pour ce service
            for ($i = 1; $i <= 2; $i++) {
                $casier = Casier::create([
                    'nom' => "Casier A{$i}",
                    'code' => "CAS-{$service->code}-0{$i}",
                    'description' => "Armoire d'archivage n°{$i} - Service {$service->nom}",
                    'service_id' => $service->id,
                ]);

                // 3. Création de 3 boîtes par casier
                for ($j = 1; $j <= 3; $j++) {
                    Boite::create([
                        'numero_boite' => "BOITE-{$service->code}-0{$i}0{$j}",
                        'capacite_max' => 50,
                        'statut' => 'disponible',
                        'service_id' => $service->id,
                        'casier_id' => $casier->id,
                    ]);
                }
            }
        }
    }
}