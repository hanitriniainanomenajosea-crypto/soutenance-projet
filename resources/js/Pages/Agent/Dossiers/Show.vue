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
    <Head :title="`Dossier ${dossier.numero_reference || dossier.reference || dossier.code}`" />

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 p-8 space-y-6">
            
            <!-- En-tête -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <button 
                    @click="goBack" 
                    class="group inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all"
                >
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Fermer
                </button>

                <span class="px-3.5 py-1.5 text-xs font-mono font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg">
                    Réf: {{ dossier.numero_reference || dossier.reference || dossier.code || 'N/A' }}
                </span>
            </div>

            <!-- Titre et Date -->
            <div class="space-y-1">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Détails du dossier</span>
                <h1 class="text-2xl font-extrabold text-slate-800 leading-tight">
                    {{ dossier.titre || dossier.intitule || dossier.nom || 'Sans intitulé' }}
                </h1>
                <p class="text-xs font-medium text-slate-400">
                    Ouvert le {{ formatDate(dossier.date_ouverture || dossier.created_at) }}
                </p>
            </div>

            <hr class="border-slate-100" />

            <!-- Emplacement Physique -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Emplacement Physique</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Boîte d'archive (s'affiche uniquement si elle est renseignée) -->
                    <div v-if="dossier.boite && dossier.boite.numero_boite" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-center gap-3">
                        <div class="p-2.5 bg-amber-100 text-amber-700 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium">Boîte d'archive</p>
                            <p class="text-sm font-bold text-slate-700">
                                Boîte {{ dossier.boite.numero_boite || dossier.boite_numero }}
                            </p>
                        </div>
                    </div>

                    <!-- Casier / Étagère -->
                    <div v-if="dossier.casier || dossier.boite?.casier" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-center gap-3">
                        <div class="p-2.5 bg-indigo-100 text-indigo-700 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium">Casier / Étagère</p>
                            <p class="text-sm font-bold text-slate-700">
                                {{ dossier.casier?.nom || dossier.boite?.casier?.nom }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Description / Notes -->
            <div>
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Description / Notes</h3>
                <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm text-slate-600 leading-relaxed min-h-[70px]">
                    {{ dossier.description || 'Aucune description disponible pour ce dossier.' }}
                </div>
            </div>

        </div>
    </div>
</template>

