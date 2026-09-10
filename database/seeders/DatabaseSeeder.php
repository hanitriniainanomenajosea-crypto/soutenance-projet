<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Service;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer les services
        $this->call(ServiceSeeder::class);

        // 2. Créer l'Administrateur
        User::create([
            'nom' => 'ADMINISTRATEUR',
            'prenom' => 'Super',
            'email' => 'admin@commune.mg',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'service_id' => null,
        ]);

        // 3. Agent État Civil
        $etatCivil = Service::where('code', 'etat_civil')->first();
        if ($etatCivil) {
            User::create([
                'nom' => 'RAKOTO',
                'prenom' => 'Jean',
                'email' => 'agent.ec@commune.mg',
                'password' => Hash::make('password123'),
                'role' => 'agent',
                'service_id' => $etatCivil->id,
            ]);
        }

        // 4. Agent Urbanisme
        $urbanisme = Service::where('code', 'urbanisme')->first();
        if ($urbanisme) {
            User::create([
                'nom' => 'RANDRIA',
                'prenom' => 'Marc',
                'email' => 'agent.urb@commune.mg',
                'password' => Hash::make('password123'),
                'role' => 'agent',
                'service_id' => $urbanisme->id,
            ]);
        }

        // 5. Agent Financement et Comptabilité
        $financement = Service::where('code', 'LIKE', '%finan%')->orWhere('nom', 'LIKE', '%Finan%')->first();
        if ($financement) {
            User::create([
                'nom' => 'RAZAFY',
                'prenom' => 'Hery',
                'email' => 'agent.fin@commune.mg',
                'password' => Hash::make('password123'),
                'role' => 'agent',
                'service_id' => $financement->id,
            ]);
        }

        // 6. Agent Ressources Humaines
        $rh = Service::where('code', 'rh')->orWhere('code', 'ressources_humaines')->first();
        if ($rh) {
            User::create([
                'nom' => 'ANDRIA',
                'prenom' => 'Soa',
                'email' => 'agent.rh@commune.mg',
                'password' => Hash::make('password123'),
                'role' => 'agent',
                'service_id' => $rh->id,
            ]);
        }
    }
}