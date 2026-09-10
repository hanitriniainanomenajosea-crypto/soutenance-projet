<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Boite;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'services' => Service::all(),
            'users' => User::with('service')->latest()->get(),
            'dossiers' => Dossier::with(['boite.casier.service'])->latest()->get(),
            'stats' => [
                'services' => Service::count(),
                'users' => User::count(),
                'boites' => Boite::count(),
                'dossiers' => Dossier::count(),
            ],
        ]);
    }

    // Enregistrement d'un nouvel utilisateur
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,agent'],
            'service_id' => ['nullable', 'exists:services,id'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'service_id' => $request->role === 'agent' ? $request->service_id : null,
        ]);

        return back()->with('success', 'Compte créé avec succès.');
    }


    // Modification d'un utilisateur existant
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'in:admin,agent'],
            'service_id' => ['nullable', 'exists:services,id'],
        ]);

        $user->update([
            'role' => $request->role,
            'service_id' => $request->role === 'agent' ? $request->service_id : null,
        ]);

        return back()->with('success', 'Utilisateur mis à jour avec succès.');
    }

    // Suppression d'un utilisateur
    public function destroyUser(User $user)
    {
        $user->delete();

        return back()->with('success', 'Utilisateur supprimé avec succès.');
    }
}