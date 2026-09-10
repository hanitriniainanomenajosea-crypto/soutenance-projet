<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
  dossier: Object,
});

const goBack = () => {
  window.history.back();
};

// Formatage de la date
const formatDate = (dateString) => {
  if (!dateString) return 'Date non renseignée';
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
};
</script>

<template>
  <Head :title="`Dossier ${dossier.code}`" />

  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md overflow-y-auto">
    
    <!-- Conteneur principal de la Modal -->
    <div class="relative w-full max-w-2xl bg-white/80 backdrop-blur-xl border border-white/80 shadow-2xl rounded-2xl p-6 sm:p-8 space-y-6 transform transition-all">
      
      <!-- En-tête / Navigation -->
      <div class="flex items-center justify-between border-b border-slate-200/60 pb-4">
        <button 
          @click="goBack"
          class="group inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-white/80 hover:bg-white rounded-xl border border-slate-200/60 shadow-sm transition-all"
        >
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Retour
        </button>

        <div class="flex items-center gap-2">
          <span class="bg-indigo-100 text-indigo-700 font-mono font-bold text-xs px-3 py-1.5 rounded-lg border border-indigo-200">
            {{ dossier.code }}
          </span>
        </div>
      </div>

      <!-- Carte Principale : Informations Générales -->
      <div class="space-y-2">
        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Fiche Dossier</span>
        <h1 class="text-2xl font-bold text-slate-800 mt-1">{{ dossier.nom }}</h1>
        <p class="text-xs text-slate-400 mt-1">
          Ouvert le {{ formatDate(dossier.date_ouverture) }}
        </p>
      </div>

      <hr class="border-slate-200/60" />

      <!-- Emplacement Physique dans L'Archivage -->
      <div>
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Emplacement Physique</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          
          <!-- Boîte d'archive -->
          <div class="p-4 bg-white/80 border border-slate-200/80 rounded-xl flex items-center gap-3 shadow-sm">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-400 font-medium">Boîte d'archive</p>
              <p class="text-sm font-bold text-slate-800">
                {{ dossier.boite?.numero_boite ?? 'Non définie' }}
              </p>
              <p v-if="dossier.boite?.code" class="text-xs text-slate-500 font-mono">
                Code: {{ dossier.boite.code }}
              </p>
            </div>
          </div>

          <!-- Casier de rangement -->
          <div class="p-4 bg-white/80 border border-slate-200/80 rounded-xl flex items-center gap-3 shadow-sm">
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-400 font-medium">Casier / Étagère</p>
              <p class="text-sm font-bold text-slate-800">
                {{ dossier.boite?.casier?.nom ?? 'Non défini' }}
              </p>
              <p v-if="dossier.boite?.casier?.code" class="text-xs text-slate-500 font-mono">
                Code: {{ dossier.boite.casier.code }}
              </p>
            </div>
          </div>

        </div>
      </div>

      <!-- Description du dossier -->
      <div>
        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Description / Notes</h3>
        <div class="p-4 bg-white/50 border border-slate-200/60 rounded-xl text-sm text-slate-700 min-h-[80px]">
          {{ dossier.description || 'Aucune description disponible pour ce dossier.' }}
        </div>
      </div>

    </div>
  </div>
</template>