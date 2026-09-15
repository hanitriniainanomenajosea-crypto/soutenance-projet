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
</script>

<template>
    
        <div class="p-6 space-y-6">
            <!-- Bouton Retour -->
            <div>
                <Link :href="route('agent.boites.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-lg text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition-all">
                    ← Retour aux boîtes
                </Link>
            </div>

            <!-- Entête Carte Boîte -->
            <div class="bg-white rounded-xl p-6 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="text-4xl">📦</span>
                    <div>
                        <h2 class="text-base font-bold text-slate-800 uppercase">{{ boite.nom }}</h2>
                        <p class="text-xs text-slate-400 mt-1">
                            Casier : <span class="font-medium text-slate-600">{{ boite.casier?.nom || 'Non assigné' }}</span>
                        </p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Dossiers contenus</p>
                    <p class="text-2xl font-black text-slate-800">{{ boite.dossiers?.length || 0 }}</p>
                </div>
            </div>

            <!-- Liste des dossiers rangés -->
            <div class="bg-white rounded-xl p-6 shadow-sm space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800">Dossiers rangés dans cette boîte</h3>
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Filtrer les dossiers..." 
                        class="text-xs border-slate-200 rounded-lg w-60 focus:ring-emerald-500 focus:border-emerald-500"
                    />
                </div>

                <!-- Tableau des dossiers -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-slate-400 border-b border-slate-100 uppercase text-[10px]">
                                <th class="py-3 px-2">Référence</th>
                                <th class="py-3 px-2">Intitulé du dossier</th>
                                <th class="py-3 px-2">Date d'ouverture</th>
                                <th class="py-3 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="dossier in filteredDossiers" :key="dossier.id" class="hover:bg-slate-50/50">
                                <td class="py-3 px-2 font-bold text-slate-700">{{ dossier.numero_reference }}</td>
                                <td class="py-3 px-2 text-slate-600">{{ dossier.titre }}</td>
                                <td class="py-3 px-2 text-slate-500">{{ dossier.date_ouverture || dossier.created_at }}</td>
                                <td class="py-3 px-2 text-right">
                                    <td class="py-3 px-2 text-right">
    <Link 
        :href="`/agent/dossiers/${dossier.id}`" 
        class="text-emerald-600 font-semibold hover:underline"
    >
        Voir
    </Link>
</td>
                                </td>
                            </tr>
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