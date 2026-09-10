<?php
namespace App\Http\Controllers;

use App\Models\Boite;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DossierController extends Controller
{
     // Affiche la liste des dossiers
    public function index()
{
    $user = auth()->user();

    $dossiers = Dossier::whereHas('boite.casier', function ($query) use ($user) {
        $query->where('service_id', $user->service_id);
    })
    ->with('boite')
    ->latest()
    ->paginate(10);

    $boites = Boite::whereHas('casier', function ($query) use ($user) {
        $query->where('service_id', $user->service_id);
    })->with('casier')->get(); // <-- LE ->with('casier') EST OBLIGATOIRE ICI

    return Inertia::render('Agent/Dossiers/Index', [
        'dossiers' => $dossiers,
        'boites' => $boites,
    ]);
}

    // Afficher le formulaire de création
    public function create(Request $request)
    {
        $user = $request->user();

        // Récupération des boîtes via leur casier
        $boites = Boite::whereHas('casier', function($q) use ($user) {
            $q->where('service_id', $user->service_id);
        })->get();

        return Inertia::render('Agent/Dossiers/Create', [
            'boites' => $boites,
        ]);
    }

    // Afficher les détails d'un dossier
public function show(Request $request, $id)
{
    $user = $request->user();

    // Récupération du dossier avec sa boîte et son casier lié
    $dossier = Dossier::whereHas('boite.casier', function($q) use ($user) {
        $q->where('service_id', $user->service_id);
    })->with(['boite.casier'])->findOrFail($id);

    return Inertia::render('Agent/Dossiers/Show', [
        'dossier' => $dossier,
    ]);
}

    public function store(Request $request)
{
    $validated = $request->validate([
        'numero_reference' => 'required|string|max:255',
        'titre'            => 'required|string|max:255',
        'description'      => 'nullable|string',
        'boite_id'         => 'required|exists:boites,id',
    ]);

    Dossier::create([
        'numero_reference' => $validated['numero_reference'],
        'titre'            => $validated['titre'],
        'description'      => $validated['description'] ?? null,
        'boite_id'         => $validated['boite_id'],
        'user_id'          => auth()->id(), // Associe le dossier à l'utilisateur connecté
    ]);

    return redirect()->back()->with('success', 'Dossier créé avec succès !');
}

    public function destroy($id)
{
    $dossier = Dossier::findOrFail($id);
    $dossier->delete();

    return redirect()->back()->with('success', 'Dossier supprimé avec succès');
}    
}