<?php
namespace App\Http\Controllers;

use App\Models\Boite;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoiteController extends Controller
{
    public function index()
{
    $user = auth()->user();

    $boites = Boite::whereHas('casier', function ($query) use ($user) {
        $query->where('service_id', $user->service_id);
    })
    ->with('casier')
    ->withCount('dossiers')
    ->orderBy('id', 'asc')
    ->latest()
    ->get();

    return Inertia::render('Agent/Boites/Index', [
        'boites' => $boites,
    ]);
}
    public function destroy($id)
{
    $boite = Boite::findOrFail($id);
    
    if ($boite->casier_id) {
        \App\Models\Casier::destroy($boite->casier_id);
    }
    
    $boite->delete();

    return redirect()->back();
}

    // Voir le contenu d'une boîte quand on clique dessus
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $boite = Boite::whereHas('casier', function($q) use ($user) {
            $q->where('service_id', $user->service_id);
        })->with(['casier', 'dossiers'])->findOrFail($id);

        return Inertia::render('Agent/Boites/Show', [
            'boite' => $boite,
        ]);
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'nom'          => 'nullable|string|max:255',
        'numero_boite' => 'nullable|string|max:255',
        'statut'       => 'nullable|string',
        'max_dossiers' => 'nullable|integer|min:1',
    ]);

    $boite = Boite::findOrFail($id);

    // Récupère la nouvelle limite
    $capacite = $request->input('max_dossiers') ?? $request->input('capacite') ?? 30;

    // Mise à jour de la boîte
    $boite->update([
        'code'     => $request->input('numero_boite') ?? $boite->code,
        'statut'   => $request->input('statut', 'Disponible'),
        'capacite' => (int) $capacite,
    ]);

    // Mettre à jour le nom du casier lié
    if ($boite->casier && $request->filled('nom')) {
        $boite->casier->update([
            'nom' => $request->input('nom'),
        ]);
    }

    return redirect()->back()->with('success', 'Modifications enregistrées');
}

public function store(Request $request)
{
    $validated = $request->validate([
        'nom'          => 'nullable|string|max:255',
        'numero_boite' => 'nullable|string|max:255',
        'statut'       => 'nullable|string',
        'max_dossiers' => 'nullable', 
    ]);

    // Conversion forcée en entier
    $capacite = (int) ($request->input('max_dossiers') ?? 30);

    Boite::create([
        'nom'      => $request->input('nom') ?? 'Casier Sans Nom',
        'code'     => $request->input('numero_boite'),
        'capacite' => $capacite > 0 ? $capacite : 30,
    ]);

    return redirect()->back();
}

}