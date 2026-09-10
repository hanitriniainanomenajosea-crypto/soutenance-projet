<script setup>
import { ref } from 'vue';
import AgentLayout from '@/Layouts/AgentLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; 

const props = defineProps({
    auth: Object,
    boites: Array,
});

const theme = {
    bgLight: 'bg-emerald-50',
    btn: 'bg-emerald-600 hover:bg-emerald-700',
    badge: 'bg-emerald-100 text-emerald-700',
};
 
// Modals & États
const showCasierModal = ref(false);
const showDossierModal = ref(false);
const selectedBoite = ref(null);
const activeDropdown = ref(null);

// Formulaire Casier
const casierForm = useForm({
    nom: '',
    numero_boite: '',
    statut: 'Disponible',
    capacite: 35,
});

// Formulaire Dossier
const dossierForm = useForm({
    boite_id: null,
    titre: '',
    numero_reference: '',
    date_ouverture: '',
    description:'',
    fichiers: [],
});

const openDossierModal = (boite) => {
    selectedBoite.value = boite;
    dossierForm.boite_id = boite.id;
    showDossierModal.value = true;
    activeDropdown.value = null;
};

// Lors de la création
const submitCasier = () => {
    casierForm.capacite = Number(casierForm.max_dossiers) || 30;
    casierForm.post(route('agent.casiers.store'), {
        preserveScroll: true,
        onSuccess: () => showCasierModal.value = false,
    });
};

// Permet de stocker les fichiers choisis
const handleFileUpload = (event) => {
    dossierForm.fichiers = Array.from(event.target.files);
};

// Envoie le formulaire au backend avec support des fichiers
const submitDossier = () => {
    dossierForm.post(route('agent.dossiers.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showDossierModal.value = false;
            dossierForm.reset();
        },
        onError: (errors) => {
            console.error("Erreurs de validation Laravel :", errors);
        }
    });
};

const deleteBoite = (boiteId) => {
    if (confirm('Voulez-vous vraiment supprimer cette boîte ?')) {
        router.delete(route('agent.boites.destroy', boiteId));
    }
};
// Modale d'Édition / Modification de Casier
const showEditModal = ref(false);
const editingBoite = ref(null);

const editForm = useForm({
    id: null,
    nom: '',
    numero_boite: '',
    statut: 'Disponible',
    max_dossiers: 30,
    capacite:30,
});

const openEditModal = (boite) => {
    // 1. Récupération de l'ID du casier (ou de la boîte)
    editForm.id = boite.casier ? boite.casier.id : boite.id;
    editForm.nom = boite.casier ? boite.casier.nom : (boite.nom || boite.code);
    editForm.numero_boite = boite.code;
    editForm.statut = boite.statut;
    
    const limiteActuelle = boite.capacite || boite.max_dossiers || 30;
    editForm.max_dossiers = limiteActuelle;
    editForm.capacite = limiteActuelle;

    showEditModal.value = true;
};

const submitEdit = () => {
    // conversion en nombre de la valeur tapée dans le champ
    const nouvelleLimite = Number(editForm.max_dossiers) || Number(editForm.capacite) || 30;
    
    // Aligne les deux clés pour le backend
    editForm.max_dossiers = nouvelleLimite;
    editForm.capacite = nouvelleLimite;

    editForm.put(route('agent.casiers.update', { id: editForm.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
        onError: (errors) => {
            console.error('Erreur :', errors);
        }
    });
};
</script>

<template>
    <AgentLayout>
        <div class="p-6">
            <!-- Barre de titre de section -->
            <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm mb-6">
                <h1 class="text-base font-bold text-slate-800">Casiers & Boîtes</h1>
                <button @click="showCasierModal = true" :class="['px-3 py-1.5 text-xs font-bold text-white rounded-lg transition-all', theme.btn]">
                    + Nouveau Casier
                </button>
            </div>

            <!-- Aucune boîte -->
            <div v-if="!boites || boites.length === 0" class="bg-white rounded-xl p-12 text-center text-slate-400 text-xs shadow-sm">
                Aucun casier ni boîte enregistrés.
            </div>

            <!-- Grille avec cartes VERTICALES COMPACTES -->
       <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
            <div 
                v-for="boite in boites" 
                :key="boite.id" 
                class="group relative bg-white rounded-xl p-3 border-2 border-slate-200 hover:border-blue-500 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between h-44 text-center overflow-visible"
            >
        <!-- Ligne décorative dégradée en haut -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 opacity-80 group-hover:opacity-100 transition-opacity rounded-t-xl"></div>

        <!-- En-tête : Badge compact et Menu 3 points -->
        <div class="flex items-center justify-between w-full pt-0.5">
            <span :class="['text-[9px] font-bold px-1.5 py-0.5 rounded-full border shadow-sm', theme.badge]">
                {{ boite.statut || 'Disponible' }}
            </span>

    <!-- Menu 3 points -->
        <div class="relative">
            <button 
                @click="activeDropdown = activeDropdown === boite.id ? null : boite.id" 
                class="p-0.5 text-slate-400 hover:text-slate-700 rounded hover:bg-slate-100 transition-colors"
            >
                ⋮
            </button>

        <!-- Dropdown -->
            <div 
                v-if="activeDropdown === boite.id" 
                class="absolute right-0 mt-1 w-36 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-30 text-xs text-left"
            >
                <button 
                    @click="openDossierModal(boite)" 
                    class="w-full px-3 py-1.5 hover:bg-slate-50 text-slate-700 flex items-center gap-1.5"
                >
                    + Nouveau dossier
                </button>
                <button 
                    @click="openEditModal(boite)" 
                    class="w-full text-left px-4 py-2 text-xs text-amber-600 hover:bg-amber-50 flex items-center gap-2">
                        🛠️ Modifier la boîte
                </button>
                <button 
                    @click="deleteBoite(boite.id)" 
                    class="w-full px-3 py-1.5 hover:bg-red-50 text-red-600 flex items-center gap-1.5"
                >
                    Supprimer
                    </button>
                </div>
            </div>
        </div>

        <!--  boîte -->
        <Link :href="route('agent.boites.show', boite.id)" class="flex flex-col items-center justify-center my-auto group/item">
            <div class="w-10 h-10 rounded-xl bg-slate-50 group-hover/item:bg-blue-50 flex items-center justify-center transition-colors mb-1">
                <span class="text-2xl group-hover/item:scale-110 transition-transform duration-200">📦</span>
            </div>

            <h3 class="font-bold text-slate-800 text-xs group-hover/item:text-blue-600 transition-colors line-clamp-1">
                {{ boite.casier?.nom || boite.nom || boite.code || 'Sans nom'}}
            </h3>
        </Link>

        <!-- Pied : Nombre de dossiers -->
        <div class="pt-1.5 border-t border-slate-100">
            <p class="text-[10px] font-semibold text-slate-400">
                {{ boite.dossiers_count ?? 0 }} / {{ boite.capacite || 30 }} dossiers
            </p>
        </div>
    </div>
</div>

            <!-- MODAL NOUVEAU CASIER -->
            <div v-if="showCasierModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl space-y-3">
                    <h3 class="text-sm font-bold text-slate-800">Créer un Nouveau Casier</h3>
                    <form @submit.prevent="submitCasier" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Nom du casier</label>
                            <input v-model="casierForm.nom" type="text" placeholder="Ex: Casier Urbanisme A" class="w-full rounded-lg border-slate-200 text-xs" required />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-1">Numéro de boîte</label>
                            <input v-model="casierForm.numero_boite" type="text" placeholder="Ex: N° URB-B01" class="w-full rounded-lg border-slate-200 text-xs" required />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Statut</label>
                                <select v-model="casierForm.statut" class="w-full rounded-lg border-slate-200 text-xs">
                                    <option value="Disponible">Disponible</option>
                                    <option value="Réservé">Réservé</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-600 mb-1">Limite dossiers</label>
                                <input v-model="casierForm.max_dossiers" type="number" min="1" class="w-full rounded-lg border-slate-200 text-xs" required />
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showCasierModal = false" class="px-3 py-1.5 font-bold text-slate-600 bg-slate-100 rounded-lg">Annuler</button>
                            <button type="submit" :class="['px-3 py-1.5 font-bold text-white rounded-lg', theme.btn]">Créer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL NOUVEAU DOSSIER -->
            <div v-if="showDossierModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-xl space-y-3">
                    <h3 class="text-sm font-bold text-slate-800">Nouveau Dossier</h3>
                    <p class="text-xs text-slate-400">Dans la boîte : <span class="font-bold text-slate-600">{{ selectedBoite?.nom }}</span></p>

                    <form @submit.prevent="submitDossier" class="space-y-3 text-xs">
    <!-- Intitulé -->
    <div>
        <label class="block font-semibold text-slate-600 mb-1">Intitulé / Nom du dossier</label>
        <input v-model="dossierForm.titre" type="text" placeholder="Ex: Permis de construire M. X" class="w-full rounded-lg border-slate-200 text-xs" required />
    </div>

    <!-- Référence / Code -->
    <div>
        <label class="block font-semibold text-slate-600 mb-1">Code / Référence</label>
        <input v-model="dossierForm.numero_reference" type="text" placeholder="Ex: URB-2026-001" class="w-full rounded-lg border-slate-200 text-xs" required />
    </div>

    <!-- Date d'ouverture -->
    <div>
        <label class="block font-semibold text-slate-600 mb-1">Date d'ouverture</label>
        <input v-model="dossierForm.date_ouverture" type="date" class="w-full rounded-lg border-slate-200 text-xs" required />
    </div>

    <!-- Documents / Fichiers joints -->
    <div>
        <label class="block font-semibold text-slate-600 mb-1">Documents / Images jointes (Max 3)</label>
        <input type="file" multiple @change="handleFileUpload" class="w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100" />
    </div>

    <!-- Description -->
    <div>
        <label class="block font-semibold text-slate-600 mb-1">Description (Optionnel)</label>
        <textarea v-model="dossierForm.description" rows="2" class="w-full rounded-lg border-slate-200 text-xs" placeholder="Résumé du contenu du dossier..."></textarea>
    </div>

    <!-- Boutons -->
    <div class="flex justify-end gap-2 pt-2">
        <button type="button" @click="showDossierModal = false" class="px-3 py-1.5 font-bold text-slate-600">Annuler</button>
        <button type="submit" :class="['px-3 py-1.5 font-bold text-white rounded-lg', theme.btn]">Enregistrer le dossier</button>
    </div>
</form>
                </div>
            </div>
        </div>
               <!-- Modale de Modification de Boîte -->
            <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center z-50">
                <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl border border-slate-100">
                    <h3 class="text-base font-bold text-slate-800 mb-4">Modifier le Casier / Boîte</h3>
        
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nom du casier</label>
                    <input v-model="editForm.nom" type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
                </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Numéro de boîte</label>
                <input v-model="editForm.numero_boite" type="text" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Statut</label>
                    <select v-model="editForm.statut" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="Disponible">Disponible</option>
                        <option value="Plein">Plein</option>
                        <option value="Archivé">Archivé</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Limite dossiers</label>
                    <input v-model="editForm.max_dossiers" type="number" min="1" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none" required />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showEditModal = false" class="px-4 py-2 text-xs font-medium text-slate-500 hover:bg-slate-100 rounded-xl">
                    Annuler
                </button>
                <button type="submit" :disabled="editForm.processing" class="px-4 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
    </AgentLayout>
</template>