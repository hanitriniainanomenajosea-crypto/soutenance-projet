<script setup>
import { computed } from 'vue';
import AgentLayout from '@/Layouts/AgentLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    auth: Object,
    stats: Object
});


// Palette de couleurs dynamique selon le champ couleur_theme du service
const theme = computed(() => {
    const color = props.auth?.user?.service?.couleur_theme ?? 'blue';
    
    const themes = {
        blue: {
            banner: 'from-slate-900 via-blue-950 to-slate-900',
            badge: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            text: 'text-blue-600',
            hoverBg: 'hover:bg-blue-50',
            pulse: 'bg-blue-400'
        },
        emerald: {
            banner: 'from-slate-900 via-emerald-950 to-slate-900',
            badge: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
            text: 'text-emerald-600',
            hoverBg: 'hover:bg-emerald-50',
            pulse: 'bg-emerald-400'
        },
        amber: {
            banner: 'from-slate-900 via-amber-950 to-slate-900',
            badge: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
            text: 'text-amber-600',
            hoverBg: 'hover:bg-amber-50',
            pulse: 'bg-amber-400'
        },
        purple: {
            banner: 'from-slate-900 via-purple-950 to-slate-900',
            badge: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            text: 'text-purple-600',
            hoverBg: 'hover:bg-purple-50',
            pulse: 'bg-purple-400'
        }
    };

    return themes[color] || themes.blue;
});
</script>

<template>
    <AgentLayout>
    <div class="min-h-screen bg-slate-50">
        
        <!-- Contenu Principal -->
        <main class="p-6 md:p-8 max-w-7xl mx-auto space-y-8">
        
            <div :class="['relative overflow-hidden rounded-3xl bg-gradient-to-br p-8 md:p-10 text-white shadow-2xl transition-all duration-500', theme.banner]">
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-4">
                        <span :class="['inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider border backdrop-blur-md shadow-inner', theme.badge]">
                            <span :class="['w-2.5 h-2.5 rounded-full animate-pulse', theme.pulse]"></span>
                            Service : {{ auth?.user?.service?.nom ?? 'Non défini' }}
                        </span>
                    </div>
                    <h2 class="text-3xl md:text-4xl font-extrabold tracking-tight mb-2">
                        Espace {{ auth?.user?.service?.nom ?? 'Gestion' }}
                    </h2>
                    <p class="text-slate-300 text-sm md:text-base max-w-2xl leading-relaxed">
                        Bienvenue sur votre espace d'archivage réservé au service {{ auth?.user?.service?.nom }}.
                    </p>
                </div>
            </div>

            <!-- Cartes Statistiques Interactives -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                
                <!-- Carte Boîtes -->
                <div class="group bg-white rounded-3xl p-8 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity duration-300">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" /><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                    </div>
                    <p class="text-sm font-bold text-indigo-500 uppercase tracking-widest mb-2">Vos Boîtes</p>
                    <h3 class="text-6xl font-black text-slate-800 mb-6 group-hover:text-indigo-900 transition-colors">{{ stats?.boites_count ?? 0 }}</h3>
                    <Link href="/agent/boites" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 px-5 py-3 rounded-xl transition-all w-fit">
                        Accéder aux boîtes &rarr;
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </Link>
                </div>

                <!-- Carte Dossiers -->
                <div class="group bg-white rounded-3xl p-8 border border-slate-200/60 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity duration-300">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" /><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" /></svg>
                    </div>
                    <p class="text-sm font-bold text-indigo-500 uppercase tracking-widest mb-2">Vos Dossiers</p>
                    <h3 class="text-6xl font-black text-slate-800 mb-6 group-hover:text-indigo-900 transition-colors">{{ stats?.dossiers_count ?? 0 }}</h3>
                    <Link href="/agent/dossiers" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 px-5 py-3 rounded-xl transition-all w-fit">
                        Consulter les dossiers &rarr;
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                    </Link>
                </div>
            </div>
            <!-- Footer global -->
<footer class="mt-auto py-6 border-t border-slate-200/60 bg-white/50 backdrop-blur-sm text-slate-500 text-xs">
  <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
    
    <!-- Branding & Droits -->
    <div class="flex items-center gap-2">
      <span class="font-bold text-slate-700">Commune Urbaine de Mahajanga</span>
      <span class="text-slate-300">•</span>
      <span>© 2026 Tous droits réservés</span>
    </div>

    <!-- Info Application & Version -->
    <div class="flex items-center gap-4 text-slate-400">
      <span>Système de Gestion des Archives Numériques</span>
      <span class="px-2 py-0.5 rounded-full bg-slate-100 text-[10px] font-mono text-slate-600 font-semibold border border-slate-200">
        v1.0
      </span>
    </div>

  </div>
</footer>

        </main>
    </div>
    </AgentLayout>
</template>