<?php
use App\Http\Controllers\CasierController;
use App\Http\Controllers\BoiteController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirection d'accueil vers le login
Route::get('/', function () {
    return redirect()->route('login');
});

// Profil utilisateur
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    
    // Casiers
    Route::post('/agent/casiers', [CasierController::class, 'store'])->name('agent.casiers.store');
    // Casiers
    Route::post('/agent/casiers', [CasierController::class, 'store'])->name('agent.casiers.store');
    Route::put('/agent/casiers/{id}', [CasierController::class, 'update'])->name('agent.casiers.update');

    // Dossiers
    Route::get('/agent/dossiers', [DossierController::class, 'index'])->name('agent.dossiers.index'); // <-- AJOUTER CETTE LIGNE
    Route::get('/agent/dossiers/create', [DossierController::class, 'create'])->name('agent.dossiers.create');
    Route::post('/agent/dossiers', [DossierController::class, 'store'])->name('agent.dossiers.store');
    Route::get('/agent/dossiers/{id}', [DossierController::class, 'show'])->name('agent.dossiers.show');
    Route::delete('/agent/dossiers/{id}', [DossierController::class, 'destroy'])->name('agent.dossiers.destroy');
    
    // Boîtes
    Route::get('/agent/boites', [BoiteController::class, 'index'])->name('agent.boites.index');
    Route::get('/agent/boites/create', [BoiteController::class, 'create'])->name('agent.boites.create');
    Route::get('/agent/boites/{id}', [BoiteController::class, 'show'])->name('agent.boites.show');
    Route::put('/agent/boites/{boite}', [BoiteController::class, 'update'])->name('agent.boites.update');

    Route::delete('/agent/boites/{id}', [\App\Http\Controllers\BoiteController::class, 'destroy'])->name('agent.boites.destroy');
});

// Routes protégées par authentification
Route::middleware(['auth', 'verified'])->group(function () {

    // Redirection centrale selon le rôle
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('agent.dashboard');
    })->name('dashboard');

    // Espace Administrateur
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    
    // Ajouts pour la modification et la suppression d'utilisateurs :
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
});

    // Espace Agent (avec vérification du service)
    Route::middleware(['check.service'])->prefix('agent')->name('agent.')->group(function () {
        
      
    // Dashboard Agent
Route::get('/dashboard', function () {
    $user = auth()->user()->load('service');

    return Inertia::render('Agent/Dashboard', [
        'auth' => ['user' => $user],
        'stats' => [
            'boites_count' => \App\Models\Boite::whereHas('casier', function($q) use ($user) {
                $q->where('service_id', $user->service_id);
            })->count(),
            'dossiers_count' => \App\Models\Dossier::whereHas('boite.casier', function($q) use ($user) {
                $q->where('service_id', $user->service_id);
            })->count(),
        ]
    ]);
})->name('dashboard');

        // Gestion des Boîtes
        Route::get('/boites', [BoiteController::class, 'index'])->name('boites.index');

        // Gestion des Casiers
Route::post('/agent/casiers', [\App\Http\Controllers\CasierController::class, 'store'])->name('agent.casiers.store');

// Gestion des Boîtes
Route::get('/agent/boites', [\App\Http\Controllers\BoiteController::class, 'index'])->name('agent.boites.index');
Route::get('/agent/boites/{id}', [\App\Http\Controllers\BoiteController::class, 'show'])->name('agent.boites.show');

// Gestion des Dossiers
Route::get('/agent/dossiers', [\App\Http\Controllers\DossierController::class, 'index'])->name('agent.dossiers.index');
Route::get('/agent/dossiers/create', [\App\Http\Controllers\DossierController::class, 'create'])->name('agent.dossiers.create');
Route::post('/agent/dossiers', [\App\Http\Controllers\DossierController::class, 'store'])->name('agent.dossiers.store');
    });
});

require __DIR__ . '/auth.php';