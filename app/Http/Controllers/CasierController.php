<?php
namespace App\Http\Controllers;
use App\Models\Casier;
use App\Models\Boite;
use Illuminate\Http\Request;
class CasierController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'nom'          => 'required|string|max:255',
        'numero_boite' => 'nullable|string|max:255',
        'statut'       => 'nullable|string',
        'max_dossiers' => 'nullable|integer|min:1',
        'capacite'     => 'nullable|integer|min:1',
    ]);

    $user = auth()->user();

    // 1. Création du casier
    $casier = Casier::create([
        'code'       => 'C-' . strtoupper(substr(uniqid(), -4)),
        'nom'        => $request->input('nom'),
        'service_id' => $user->service_id ?? null,
    ]);

    // 2. Récupération exacte de la capacité saisie 
    $capacite = $request->input('max_dossiers') 
             ?? $request->input('capacite') 
             ?? 30;
    $serviceNom = auth()->user()->service->nom ?? 'SER';
    $prefixe = strtoupper(substr($serviceNom, 0, 3));         
    $numBoite = $request->input('numero_boite') ?? ($prefixe . '-N°' . $casier->id);

    // 3. Enregistrement dans la table boites
    Boite::create([
        'code'      => $numBoite,
        'nom'       => $numBoite,
        'statut'    => $request->input('statut', 'Disponible'),
        'capacite'  => (int) $capacite,
        'casier_id' => $casier->id,
    ]);

    return redirect()->back();
}

public function update(Request $request, $id)
{
    // 1. Trouver le casier par son ID
    $casier = Casier::findOrFail($id);

    // 2. Mettre à jour le nom du casier
    if ($request->filled('nom')) {
        $casier->update([
            'nom' => $request->input('nom'),
        ]);
    }

    // 3. Récupérer la nouvelle valeur
    $nouvelleCapacite = $request->input('max_dossiers') ?? $request->input('capacite');

    // 4. Mettre à jour la boîte rattachée (table `boites`)
    if ($nouvelleCapacite !== null) {
        // Recherche la boîte soit par la relation casier, soit directement par casier_id
        $boite = $casier->boite ?? \App\Models\Boite::where('casier_id', $casier->id)->first();

        if ($boite) {
            $boite->update([
                'code'     => $request->input('numero_boite') ?? $boite->code,
                'statut'   => $request->input('statut') ?? $boite->statut,
                'capacite' => (int) $nouvelleCapacite,
            ]);
        }
    }
    
    return redirect()->back();
}
}