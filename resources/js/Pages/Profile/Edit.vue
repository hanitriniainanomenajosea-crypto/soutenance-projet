<script setup>
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

// Formulaire 1 : Infos
const profileForm = useForm({
    prenom: user.prenom || user.name || '',
    email: user.email || '',
});

// Formulaire 2 : Mot de passe
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.patch(route('profile.update'));
};

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <Head title="Mon Profil" />

    <div class="min-h-screen bg-slate-100 py-10 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-4xl mx-auto space-y-8">
            
            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Paramètres du Compte</h1>
                    <p class="text-slate-500 text-sm mt-1">Gérez vos informations personnelles et la sécurité de votre accès.</p>
                </div>
                <Link :href="route('dashboard')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                    ← Retour au tableau de bord
                </Link>
            </div>

            <!-- Carte 1 : Profil -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Informations Personnelles</h2>
                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Prénom / Nom</label>
                        <input v-model="profileForm.prenom" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Adresse Email</label>
                        <input v-model="profileForm.email" type="email" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required />
                    </div>

                    <button :disabled="profileForm.processing" type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-xl text-sm transition">
                        Enregistrer les modifications
                    </button>
                </form>
            </div>

            <!-- Carte 2 : Sécurité -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Changer le Mot de Passe</h2>
                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Mot de passe actuel</label>
                        <input v-model="passwordForm.current_password" type="password" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nouveau mot de passe</label>
                        <input v-model="passwordForm.password" type="password" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Confirmer le nouveau mot de passe</label>
                        <input v-model="passwordForm.password_confirmation" type="password" class="w-full px-4 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none text-sm" required />
                    </div>

                    <button :disabled="passwordForm.processing" type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold px-5 py-2 rounded-xl text-sm transition">
                        Mettre à jour le mot de passe
                    </button>
                </form>
            </div>

        </div>
    </div>
</template>