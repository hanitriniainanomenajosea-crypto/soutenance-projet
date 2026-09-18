<script setup>
import { ref, computed } from 'vue';
import AgentLayout from '@/Layouts/AgentLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import CreateModal from './Create.vue';

const props = defineProps({
  dossiers: {
    type: Object,
    default: () => ({ data: [], links: [] })
  }, 
  boites: {
    type:Array,
    default:() => []
  }
});

// Recherche dynamique en temps réel
const search = ref('');

const filteredDossiers = computed(() => {
    const list = props.dossiers?.data || [];
  if (!search.value.trim()) return list;
  const q = search.value.toLowerCase();
  return list.filter(d => 
    d.intitule?.toLowerCase().includes(q) ||
    d.numero_reference?.toLowerCase().includes(q) ||
    d.boite?.nom?.toLowerCase().includes(q)
  );
});

// Formatage de date lisible
const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};
const selectedDossier = ref(null);
const showDetailsModal = ref(false);
const showCreateModal = ref(false);
const openShowModal = (dossier) => {
    selectedDossier.value = dossier;
    showDetailsModal.value = true;
};
const showEditModal = ref(false);

// Formulaire réactif pour l'édition
const editForm = useForm({
    id: null,
    titre: '',
    numero_reference: '',
    boite_id: '',
    description: '',
});

// Fonction pour ouvrir la modale d'édition et charger les données
const openEditModal = (dossier) => {
    editForm.id = dossier.id;
    editForm.titre = dossier.titre || dossier.intitule || '';
    editForm.numero_reference = dossier.numero_reference || dossier.reference || '';
    editForm.boite_id = dossier.boite_id || '';
    editForm.description = dossier.description || dossier.mots_cles || '';
    
    showEditModal.value = true;
};

// Fonction pour envoyer les modifications au serveur
const updateDossier = () => {
    editForm.put(`/agent/dossiers/${editForm.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Fonction pour supprimer le dossier
const deleteDossier = (id) => {
    if (confirm('Voulez-vous vraiment supprimer ce dossier ?')) {
        router.delete(`/agent/dossiers/${id}`);
    }
};

</script>

<template>
  <AgentLayout>
  <Head title="Gestion des Dossiers" />

  <div class="min-h-screen bg-slate-900/5 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- En-tête de page -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Vos Dossiers d'Archives</h1>
          <p class="text-sm text-slate-500 mt-1">Consultez et gérez les dossiers rattachés à votre service.</p>
        </div>

    <button 
            @click="showCreateModal = true" 
            type="button" 
            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-sm transition"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
            Nouveau dossier
    </button>
    </div>

      <!-- Barre de recherche et filtres -->
      <div class="bg-white/70 backdrop-blur-xl border border-white/80 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="relative w-full sm:w-96">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input 
            v-model="search"
            type="text" 
            placeholder="Rechercher par nom, code ou boîte..." 
            class="w-full pl-10 pr-4 py-2 bg-white/60 border border-slate-200/80 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition-all duration-200"
          />
        </div>

        <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider self-end sm:self-auto">
          Total : <span class="text-indigo-600 font-bold text-sm ml-1">{{ filteredDossiers.length }}</span> dossier(s)
        </div>
      </div>

      <!-- Tableau des Dossiers Réorganisé -->
<div class="overflow-x-auto bg-white rounded-2xl border border-slate-100 shadow-sm">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                <th class="py-4 px-4">N°</th>
                <th class="py-4 px-4">NUMÉRO DE DOSSIER</th>
                <th class="py-4 px-4">Intitulé du dossier</th>
                <th class="py-4 px-4">Boîte d'emplacement</th>
                <th class="py-4 px-4">Date d'ouverture</th>
                
                <th class="py-4 px-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="(dossier, index) in filteredDossiers" :key="dossier.id" class="hover:bg-slate-50/60 transition-colors">
                <!-- 1. Numéro -->
                <td class="py-3 px-4 font-semibold text-slate-500">
                    #{{ index + 1 }}
                </td>

                <!-- 5. Référence -->
                <td class="py-3 px-4 font-mono text-blue-600 font-semibold">
                    {{ dossier.numero_reference }}
                </td>

                <!-- 2. Intitulé -->
                <td class="py-3 px-4 font-bold text-slate-800">
                    {{ dossier.titre || dossier.intitule }}
                </td>

                <!-- 3. Boîte d'emplacement (Casier + Numéro de boîte) -->
<td class="py-3 px-4">
    <div class="flex flex-col">
        <!-- Nom du Casier (ex: Casier A, Casier B) -->
        <span class="font-semibold text-slate-800 text-xs">
            {{ dossier.boite?.casier?.nom || dossier.boite?.casier?.libelle || 'Casier non spécifié' }}
        </span>
        
    </div>
</td>

                <!-- 4. Date -->
                <td class="py-3 px-4 text-slate-500">
                    {{ (dossier.date_ouverture || dossier.created_at) ? new Date(dossier.date_ouverture || dossier.created_at).toLocaleDateString('fr-FR') : 'N/A' }}
                </td>

               

                <!-- 7. Actions -->
                <td class="py-3 px-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button @click="openEditModal(dossier)" class="text-amber-600 hover:bg-amber-50 px-2 py-1 rounded-md font-medium text-[11px]">
                            Modifier
                        </button>
                        <button @click="deleteDossier(dossier.id)" class="text-red-600 hover:bg-red-50 px-2 py-1 rounded-md font-medium text-[11px]">
                            Supprimer
                        </button>
                        <button 
                        @click="openShowModal(dossier)" 
                        title="Voir le contenu du dossier"
                        class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors inline-flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                    </div>
                </td>
            </tr>

            <!-- Si aucun dossier -->
            <tr v-if="!dossiers || dossiers.length === 0">
                <td colspan="7" class="py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400">
                            📁
                        </div>
                        <span>Aucun dossier trouvé.</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- Boutons 1, 2, 3, Suivant -->
<div v-if="dossiers && dossiers.links && dossiers.links.length > 1" class="flex justify-end items-center space-x-1 mt-6">
    <template v-for="(link, index) in dossiers.links" :key="index">
        <!-- Bouton grisé s'il n'y a pas de page -->
        <div 
            v-if="link.url === null" 
            class="px-3 py-1.5 text-xs text-slate-400 bg-slate-100 rounded-lg cursor-not-allowed"
            v-html="link.label"
        />
        <!-- Bouton cliquable vers la page -->
        <Link 
            v-else 
            :href="link.url" 
            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
            :class="{ 
                'bg-blue-600 text-white': link.active, 
                'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200': !link.active 
            }"
            v-html="link.label"
            preserve-scroll
        />
    </template>
</div>
</div>
    </div>
  </div>

        <!-- Modale pour Modifier un dossier -->
<div v-if="showEditModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl border border-slate-100 space-y-4">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Modifier le dossier</h2>
            <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">×</button>
        </div>

        <form @submit.prevent="updateDossier" class="space-y-4 text-xs">
            <!-- Intitulé -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">INTITULÉ DU DOSSIER</label>
                <input v-model="editForm.titre" type="text" class="w-full rounded-xl border border-slate-200 p-2.5 focus:ring-2 focus:ring-indigo-500" required />
            </div>

            <!-- Numéro de dossier / Référence -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">NUMÉRO DE DOSSIER</label>
                <input v-model="editForm.numero_reference" type="text" class="w-full rounded-xl border border-slate-200 p-2.5 focus:ring-2 focus:ring-indigo-500" required />
            </div>

            <!-- Boîte d'emplacement -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">BOÎTE D'EMPLACEMENT</label>
                <select v-model="editForm.boite_id" class="w-full rounded-xl border border-slate-200 p-2.5 focus:ring-2 focus:ring-indigo-500">
                    <option value="">Sélectionner une boîte</option>
                    <option v-for="boite in boites" :key="boite.id" :value="boite.id">
                        {{ boite.nom || boite.code }}
                    </option>
                </select>
            </div>

            <!-- Boutons de validation -->
            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" @click="showEditModal = false" class="px-4 py-2 text-slate-600 bg-slate-100 rounded-xl">
                    Annuler
                </button>
                <button type="submit" :disabled="editForm.processing" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700">
                    Enregistrer
                </button>
            </div>
        </form>

    </div>
</div>


            <!-- Modale pour Voir les détails du dossier -->
<div v-if="showDetailsModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-xl border border-slate-100 space-y-4">
        
        <!-- En-tête de la modale avec bouton Fermer -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Détails du dossier</h2>
            <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg text-lg font-bold">
                ×
            </button>
        </div>

        <div class="space-y-3 text-xs">
            <!-- Intitulé du dossier -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-slate-400 block mb-1 uppercase font-semibold">Intitulé du dossier</span>
                <p class="font-bold text-slate-800 text-sm">
                    {{ selectedDossier?.titre || selectedDossier?.intitule || 'Sans titre' }}
                </p>
            </div>

            <!-- Numéro de dossier / Référence -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-slate-400 block mb-1 uppercase font-semibold">Numéro de dossier</span>
                <p class="font-bold text-blue-600 font-mono text-xs">
                    {{ selectedDossier?.numero_reference || selectedDossier?.reference || 'N/A' }}
                </p>
            </div>

            <!-- Boîte d'emplacement & Date d'ouverture -->
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block mb-1 uppercase font-semibold">Boîte d'emplacement</span>
                    <span class="font-bold text-slate-700">
                        {{ selectedDossier?.boite ? selectedDossier.boite.nom : 'Non assigné' }}
                    </span>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-slate-400 block mb-1 uppercase font-semibold">Date d'ouverture</span>
                    <span class="font-bold text-slate-700">
                        {{ selectedDossier?.created_at ? new Date(selectedDossier.created_at).toLocaleDateString('fr-FR') : 'N/A' }}
                    </span>
                </div>
            </div>

            <!-- Description / Mots-clés -->
            <div v-if="selectedDossier?.description || selectedDossier?.mots_cles" class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-slate-400 block mb-1 uppercase font-semibold">Description</span>
                <p class="text-slate-600 font-medium">
                    {{ selectedDossier?.description || selectedDossier?.mots_cles }}
                </p>
            </div>
        </div>

        <!-- Pied de la modale -->
        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button @click="showDetailsModal = false" class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                Fermer
            </button>
        </div>

    </div>
</div>
<!-- Affichage de la modale de création -->
  <CreateModal 
    v-if="showCreateModal" 
    :boites="boites" 
    @close="showCreateModal = false" 
  />
  </AgentLayout>
</template>