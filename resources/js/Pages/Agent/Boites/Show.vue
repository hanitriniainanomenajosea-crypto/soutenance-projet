<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    boite: Object,
});
const search = ref('');

const filteredDossiers = computed(() => {
    if (!props.boite.dossiers) return [];
    return props.boite.dossiers.filter(d => 
        d.titre?.toLowerCase().includes(search.value.toLowerCase()) ||
        d.numero_reference?.toLowerCase().includes(search.value.toLowerCase())
    );
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? dateString : date.toLocaleDateString('fr-FR');
};
</script>

<template>
  <div class="p-6 space-y-6">

    <!-- Bouton Retour -->
    <div>
      <Link 
        :href="route('agent.boites.index')" 
        class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-600 hover:text-slate-800 text-sm font-semibold rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 transition-all"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Retour aux boîtes
      </Link>
    </div>

    <!-- Entête Carte Boîte -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <span class="text-4xl">📦</span>
        <div>
          <h2 class="text-lg font-bold text-slate-800 uppercase tracking-wide">
            {{ boite.nom }}
          </h2>
          <p class="text-xs text-slate-400 mt-1">
            Casier : 
            <span class="font-semibold text-slate-600">
              {{ boite.casier?.nom || boite.casier_nom || 'Non assigné' }}
            </span>
          </p>
        </div>
      </div>

      <div class="text-right">
        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Dossiers contenus</p>
        <p class="text-2xl font-black text-indigo-600">
          {{ boite.dossiers?.length || 0 }}
        </p>
      </div>
    </div>

    <!-- Liste des dossiers rangés -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-4">
      
      <!-- Barre de titre + Recherche -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h3 class="text-base font-bold text-slate-800">Dossiers rangés dans cette boîte</h3>

        <div class="relative w-full sm:w-64">
          <input 
            v-model="search"
            type="text" 
            placeholder="Filtrer les dossiers..." 
            class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
          />
        </div>
      </div>

      <!-- Tableau des dossiers -->
      <div class="overflow-x-auto rounded-xl border border-slate-100">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold tracking-wider">
              <th class="py-3 px-4">Référence</th>
              <th class="py-3 px-4">Intitulé du dossier</th>
              <th class="py-3 px-4">Date d'ouverture</th>
              <th class="py-3 px-4 text-right">Action</th>
            </tr>
          </thead>
          
          <tbody class="divide-y divide-slate-100">
            <tr 
              v-for="dossier in filteredDossiers" 
              :key="dossier.id" 
              class="hover:bg-slate-50/60 transition-colors"
            >
              <!-- Référence avec Badge -->
              <td class="py-3.5 px-4 font-mono font-bold text-indigo-600">
                <span class="px-2 py-1 bg-indigo-50 rounded-lg text-[11px]">
                  {{ dossier.numero_reference || dossier.reference || dossier.code }}
                </span>
              </td>

              <!-- Intitulé -->
              <td class="py-3.5 px-4 font-medium text-slate-700">
                {{ dossier.titre || dossier.intitule || dossier.nom }}
              </td>

              <!-- Date d'ouverture -->
              <td class="py-3.5 px-4 text-slate-500">
                {{ formatDate(dossier.date_ouverture || dossier.created_at) }}
              </td>

              <!-- Bouton d'action Inertia Link -->
              <td class="py-3.5 px-4 text-right">
                <Link 
                  :href="`/agent/dossiers/${dossier.id}`" 
                  class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors"
                >
                  <span>Voir</span>
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </td>
            </tr>

            <!-- Message d'absence de résultat -->
            <tr v-if="filteredDossiers.length === 0">
              <td colspan="4" class="py-8 text-center text-slate-400 italic">
                Aucun dossier trouvé dans cette boîte.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>