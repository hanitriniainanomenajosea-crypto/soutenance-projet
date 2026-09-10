<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';


const isMenuOpen = ref(false);

const page = usePage();
const auth = computed(() => page.props.auth);
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Barre de Navigation Supérieure Commune -->
        <nav class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-50 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="bg-slate-900 text-white p-2 rounded-lg shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div>
                    <Link :href="route('agent.dashboard')" class="text-xl font-bold text-slate-800 tracking-tight hover:text-blue-600 transition-colors">
                        Espace Service
                    </Link>
                    <span class="block text-[10px] uppercase font-bold tracking-widest text-blue-600">
                        {{ auth?.user?.service?.nom ?? 'AGENT' }}
                    </span>
                </div>
            </div>

            <!-- Menu déroulant Profil Agent -->
<div class="relative">
    <!-- Bouton Déclencheur -->
    <button 
        type="button"
        @click="isMenuOpen = !isMenuOpen"
        class="flex items-center gap-3 p-1.5 px-3 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-all cursor-pointer focus:outline-none"
    >
        <!-- Avatar avec la première lettre -->
        <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-sm shadow-sm">
            {{ auth?.user?.prenom ? auth.user.prenom.charAt(0).toUpperCase() : 'A' }}
        </div>

        <!-- Nom de l'agent -->
        <span class="text-sm font-medium text-slate-600 hidden md:block">
            Agent : <span class="text-slate-900 font-bold">{{ auth?.user?.prenom }} {{ auth?.user?.nom }}</span>
        </span>

        <!-- Flèche indicative -->
        <svg 
            class="w-4 h-4 text-slate-500 transition-transform duration-200"
            :class="{ 'rotate-180': isMenuOpen }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Overlay de fermeture au clic extérieur -->
    <div 
        v-if="isMenuOpen" 
        @click="isMenuOpen = false" 
        class="fixed inset-0 z-40"
    ></div>

    <!-- Contenu du Menu Déroulant -->
    <div 
        v-if="isMenuOpen"
        class="absolute right-0 mt-2 w-48 bg-white text-slate-800 rounded-xl shadow-lg py-1 border border-slate-200 z-50"
    >
        <!-- Mon Profil -->
        <Link 
            href="/profile"
            @click="isMenuOpen = false"
            class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors"
        >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Mon Profil
        </Link>

        <div class="border-t border-slate-100 my-1"></div>

        <!-- Déconnexion -->
        <Link 
            href="/logout" 
            method="post" 
            as="button" 
            @click="isMenuOpen = false"
            class="w-full flex items-center gap-2 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors text-left"
        >
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Déconnexion
        </Link>
    </div>
</div>
        </nav>

        <!-- Contenu Dynamique de la Page (Dashboard, Boîtes, Dossiers...) -->
        <main class="p-6 md:p-8 max-w-7xl mx-auto">
            <slot />
        </main>
    </div>
    
</template>