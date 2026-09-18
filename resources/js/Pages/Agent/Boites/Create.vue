<script setup>
import { useForm, Head } from '@inertiajs/vue3';

defineProps({
  casiers: Array
});

const form = useForm({
  numero_boite: '',
  code: '',
  casier_id: '',
});

const submit = () => {
  form.post('/agent/boites', {
    onSuccess: () => {
      form.reset();
    },
  });
};

</script>

<template>
  <Head title="Nouvelle Boîte" />

  <div class="min-h-screen bg-slate-900/5 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      
      <!-- En-tête -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Créer une nouvelle boîte d'archive</h1>
          <p class="text-sm text-slate-500 mt-1">Ajoutez un conteneur pour ranger vos futurs dossiers.</p>
        </div>
        <a 
          href="/agent/boites" 
          class="group inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-white/60 hover:bg-white backdrop-blur-md border border-slate-200/80 rounded-xl shadow-sm transition-all duration-200"
        >
          <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Retour
        </a>
      </div>

      <!-- Formulaire  -->
      <div class="bg-white/70 backdrop-blur-xl border border-white/80 shadow-xl shadow-slate-200/50 rounded-2xl p-6 sm:p-8">
        <form @submit.prevent="submit" class="space-y-6">
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Code de la boîte -->
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Code / Identifiant *</label>
              <input 
                v-model="form.code" 
                type="text" 
                required 
                placeholder="ex: BOITE-001"
                class="w-full bg-white/60 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
              />
              <p v-if="form.errors.code" class="text-xs text-rose-500 mt-1.5">{{ form.errors.code }}</p>
            </div>

          
            <!-- Numéro / Libellé de la boîte -->
            <div>
              <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Numéro / Désignation *</label>
              <input 
                v-model="form.numero_boite" 
                type="text" 
                required 
                placeholder="ex: Boîte d'Archives 2026-A"
                class="w-full bg-white/60 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
              />
              <p v-if="form.errors.numero_boite" class="text-xs text-rose-500 mt-1.5">{{ form.errors.numero_boite }}</p>
            </div>
          </div>

          <!-- Sélection du casier -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Casier d'emplacement *</label>
            <select 
              v-model="form.casier_id" 
              required 
              class="w-full bg-white/60 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 cursor-pointer"
            >
              <option value="" disabled selected>Choisir un casier...</option>
              <option v-for="c in casiers" :key="c.id" :value="c.id">
                🗄️ {{ c.nom }} (Code: {{ c.code }})
              </option>
            </select>
            <p v-if="form.errors.casier_id" class="text-xs text-rose-500 mt-1.5">{{ form.errors.casier_id }}</p>
          </div>

          <!-- Boutons -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200/60">
            <a href="/agent/boites" class="px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl">Annuler</a>
            <button 
              type="submit" 
              :disabled="form.processing"
              class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-lg shadow-indigo-500/25 disabled:opacity-50"
            >
              Enregistrer la boîte
            </button>
          </div>
          
        </form>
      </div>
    </div>
  </div>
</template>