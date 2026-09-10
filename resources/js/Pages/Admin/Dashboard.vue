<script setup>
import { onMounted, ref, computed, watch, nextTick } from 'vue';
import { useForm, usePage, Head, Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';

const props = defineProps({
    services: Array,
    users: Array,
    dossiers: Array,
    stats: Object,
});
const isMenuOpen = ref(false);
const page = usePage();
const currentUser = computed(() => page.props?.auth?.user || {});

// Modale d'ajout d'utilisateur
const showModal = ref(false);

// Modale de consultation de dossier
const showDossierModal = ref(false);
const selectedDossier = ref(null);

const openDossier = (dossier) => {
    selectedDossier.value = dossier;
    showDossierModal.value = true;
};

// Filtres
const searchQuery = ref('');
const selectedService = ref('');

// Calcul dynamique des dossiers filtrés
const filteredDossiers = computed(() => {
    if (!props.dossiers) return [];
    
    return props.dossiers.filter(dossier => {
        const query = searchQuery.value.toLowerCase();
        const matchesSearch = 
            (dossier.titre && dossier.titre.toLowerCase().includes(query)) ||
            (dossier.reference && dossier.reference.toLowerCase().includes(query));

        const matchesService = !selectedService.value || 
            (dossier.boite && dossier.boite.service_id == selectedService.value);

        return matchesSearch && matchesService;
    });
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'agent',
    service_id: '',
});

const submit = () => {
    form.post(route('admin.users.store'), {
        onSuccess: () => {
            form.reset();
            showModal.value = false;
        },
    });
};

// Modale d'édition d'utilisateur
const showEditUserModal = ref(false);
const editingUser = ref(null);

const editUserForm = useForm({
    id: null,
    role: 'agent',
    service_id: '',
});

const openEditUser = (user) => {
    editingUser.value = user;
    editUserForm.id = user.id;
    editUserForm.role = user.role;
    editUserForm.service_id = user.service_id || '';
    showEditUserModal.value = true;
};

const submitEditUser = () => {
    editUserForm.put(route('admin.users.update', editUserForm.id), {
        onSuccess: () => {
            showEditUserModal.value = false;
        },
    });
};

const deleteUser = (user) => {
    if (confirm(`Voulez-vous vraiment supprimer le compte de ${user.email} ?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};


Chart.register(...registerables);

const chartCanvas = ref(null);
let chartInstance = null;

// Données calculées
const chartData = computed(() => {
    const servicesCount = {};
    
    const list = props.dossiers || [];
    if (list.length > 0) {
        list.forEach(dossier => {
            const serviceNom = dossier.boite?.service?.nom || 'Non assigné';
            servicesCount[serviceNom] = (servicesCount[serviceNom] || 0) + 1;
        });
    } else {
        servicesCount['Aucune donnée'] = 1;
    }

    return {
        labels: Object.keys(servicesCount),
        datasets: [{
            label: 'Nombre de dossiers',
            data: Object.values(servicesCount),
            backgroundColor: [
                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                '#8b5cf6', '#ec4899', '#06b6d4', '#64748b'
            ],
            borderWidth: 0,
            hoverOffset: 6
        }]
    };
});

const renderChart = async () => {
    await nextTick();
    if (!chartCanvas.value) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = chartCanvas.value.getContext('2d');
    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: chartData.value,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            cutout: '75%'
        }
    });
};

onMounted(() => {
    setTimeout(() => {
        renderChart();
    }, 100);
});

watch(() => props.dossiers, () => {
    renderChart();
}, { deep: true });

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};
</script>

<template>
    <Head title="Tableau de Bord Admin" />

    <div class="min-h-screen bg-slate-100 font-sans">
        
        <!-- Barre de Navigation Supérieure -->
        <nav class="bg-slate-900 text-white px-6 py-3 flex items-center justify-between relative z-50">
    <!-- Gauche : Titre -->
    <div class="flex items-center gap-3">
        <div class="bg-blue-600 p-2 rounded-lg">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <div>
            <h1 class="font-bold text-lg leading-tight">Archivage Numérique</h1>
            <span class="text-xs uppercase font-semibold text-blue-400">ADMIN</span>
        </div>
    </div>

    <!-- Droite : Menu Déroulant Profil -->
        <div class="relative">
            <!-- Bouton déclencheur -->
            <button 
                type="button"
                @click="isMenuOpen = !isMenuOpen"
                class="flex items-center gap-3 p-1.5 rounded-full hover:bg-slate-800 transition-colors focus:outline-none cursor-pointer"
            >
                <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                    {{ currentUser?.name ? currentUser.name.charAt(0).toUpperCase() : 'A' }}
                </div>

                <span class="font-medium text-sm hidden md:inline-block">
                    {{ currentUser?.name || 'Administrateur' }}
                </span>

                <svg 
                    class="w-4 h-4 text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isMenuOpen }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Overlay transparent pour fermer au clic extérieur -->
            <div 
                v-if="isMenuOpen" 
                @click="isMenuOpen = false" 
                class="fixed inset-0 z-40"
            ></div>

            <!-- Contenu du Menu Déroulant -->
            <div 
                v-if="isMenuOpen"
                class="absolute right-0 mt-2 w-56 bg-white text-slate-800 rounded-2xl shadow-2xl py-2 border border-slate-100 z-50"
            >
                <!-- En-tête -->
                <div class="px-4 py-3 border-b border-slate-100">
                    <p class="text-sm font-bold text-slate-900">
                        {{ currentUser?.name || 'Administrateur' }}
                    </p>
                    <p class="text-xs text-slate-500 truncate">
                        {{ currentUser?.email || 'admin@commune.mg' }}
                    </p>
                </div>

                <!-- Liens -->
                <div class="py-1">
                    <Link 
                        :href="route('profile.edit')"
                        @click="isMenuOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 transition-colors"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Mon Profil
                    </Link>

                    <button 
                        type="button"
                        @click="showModal = true; isMenuOpen = false;" 
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 transition-colors text-left"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Nouveau Compte
                    </button>
                </div>

                <!-- Déconnexion -->
                <div class="border-t border-slate-100 pt-1">
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        @click="isMenuOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors text-left font-semibold"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Déconnexion
                    </Link>
                </div>
            </div>
        </div>
        </nav>

        <!-- Contenu Principal -->
        <main class="max-w-7xl mx-auto py-8 px-6 space-y-8">
            
            <!-- Bandeau de Bienvenue -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Tableau de Bord Global</h2>
                    <p class="text-slate-500 text-sm mt-1">Supervision de l'ensemble des services, utilisateurs et archives.</p>
                </div>
                <span class="inline-flex items-center text-xs font-semibold px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full">
                    <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Système actif
                </span>
            </div>

            <!-- Cartes Statisiques -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Services -->
    <div class="bg-white p-5 rounded-2xl border-2 border-slate-100 hover:border-blue-500/40 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-between group">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-blue-600 transition-colors">Services</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats?.services || 0 }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            🏛️
        </div>
    </div>

    <!-- Utilisateurs -->
    <div class="bg-white p-5 rounded-2xl border-2 border-slate-100 hover:border-indigo-500/40 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-between group">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-indigo-600 transition-colors">Utilisateurs</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats?.users || 0 }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            👥
        </div>
    </div>

    <!-- Boîtes d'archives -->
    <div class="bg-white p-5 rounded-2xl border-2 border-slate-100 hover:border-amber-500/40 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-between group">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-amber-600 transition-colors">Boîtes</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats?.boites || 0 }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            📦
        </div>
    </div>

    <!-- Dossiers d'archives -->
    <div class="bg-white p-5 rounded-2xl border-2 border-slate-100 hover:border-emerald-500/40 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 flex items-center justify-between group">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 group-hover:text-emerald-600 transition-colors">Dossiers</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ stats?.dossiers || 0 }}</h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            📁
        </div>
    </div>
</div>

<!-- Liste des Utilisateurs Récents -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Utilisateurs Enregistrés</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-slate-400 text-xs uppercase font-semibold">
                            <tr>
                                <th class="p-3 rounded-l-lg">Nom</th>
                                <th class="p-3">Email</th>
                                <th class="p-3">Rôle</th>
                                <th class="p-3 rounded-r-lg">Service</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50">
                                <td class="p-3 font-medium text-slate-800">{{ user.name }}</td>
                                <td class="p-3">{{ user.email }}</td>
                                <td class="p-3">
                                    <span :class="user.role === 'admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700'" class="px-2.5 py-0.5 rounded-full text-xs font-semibold">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-500">{{ user.service?.nom || '—' }}</td>
                                <td class="p-3 text-right space-x-2">
                <button 
                    type="button"
                    @click="openEditUser(user)"
                    class="px-2 py-1 text-xs font-semibold text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors"
                >
                    ✏️ Modifier
                </button>
                <button 
                    type="button"
                    @click="deleteUser(user)"
                    class="px-2 py-1 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors"
                >
                    🗑️ Supprimer
                </button>
            </td>
                            </tr>
                            <tr v-if="!users || users.length === 0">
                                <td colspan="5" class="p-4 text-center text-slate-400">Aucun utilisateur trouvé.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Carte Graphique Statistique -->
<div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-sm font-bold text-slate-800">Répartition des Archives</h3>
            <p class="text-xs text-slate-400">Volume de dossiers par service municipal</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg">
            Dynamique
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
        <!-- Graphique (à gauche) -->
        <div class="md:col-span-1 h-52 relative flex items-center justify-center">
            <canvas ref="chartCanvas"></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <span class="text-2xl font-bold text-slate-800">{{ dossiers ? dossiers.length : 0 }}</span>
                <span class="text-[10px] uppercase tracking-wider text-slate-400 font-medium">Dossiers</span>
            </div>
        </div>

        <!-- LE NOUVEAU  -->
        <div class="md:col-span-2 space-y-3">
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-100 flex justify-between items-center text-xs transition-all hover:bg-slate-100/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span class="font-medium text-slate-600">Total des services enregistrés</span>
                </div>
                <span class="font-bold text-slate-800 text-sm bg-white px-2.5 py-1 rounded-lg border border-slate-200/60 shadow-xs">{{ services ? services.length : 0 }}</span>
            </div>
            
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-100 flex justify-between items-center text-xs transition-all hover:bg-slate-100/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="font-medium text-slate-600">Total des utilisateurs actifs</span>
                </div>
                <span class="font-bold text-slate-800 text-sm bg-white px-2.5 py-1 rounded-lg border border-slate-200/60 shadow-xs">{{ users ? users.length : 0 }}</span>
            </div>
        </div>
    </div>
</div>

            <!-- Section des Dossiers Globaux avec Recherche & Filtres -->
<div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mt-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-base font-bold text-slate-800">Tous les Dossiers d'Archives</h2>
            <p class="text-xs text-slate-400">Consultez et recherchez parmi l'ensemble des dossiers archivés</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Sélecteur de Service -->
            <select 
                v-model="selectedService" 
                class="text-xs rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-blue-500 py-2 px-3 text-slate-700 font-medium"
            >
                <option value="">Tous les services</option>
                <option v-for="service in services" :key="service.id" :value="service.id">
                    {{ service.nom }}
                </option>
            </select>

            <!-- Champ de Recherche -->
            <div class="relative">
                <input 
                    type="text" 
                    v-model="searchQuery" 
                    placeholder="Rechercher par titre, ref..." 
                    class="text-xs rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-blue-500 py-2 pl-8 pr-3 w-48 md:w-60 text-slate-700"
                />
                <span class="absolute left-2.5 top-2 text-slate-400 text-xs">🔍</span>
            </div>
            <!-- Bouton Imprimer / Export PDF -->
            <button 
                type="button"
                @click="window.print()"
                class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors inline-flex items-center gap-2 shadow-sm"
            >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 002-2H5a2 2 0 002 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 0-2-2H9a2 2 0 0-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 0-2-2H7a2 2 0 0-2 2v4h10z" />
            </svg>
                Imprimer / Exporter (PDF)
            </button>
            <!-- Compteur -->
            <span class="text-xs font-bold px-2.5 py-1.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100">
                {{ filteredDossiers.length }} / {{ dossiers ? dossiers.length : 0 }}
            </span>
        </div>
    </div>

    <!-- Tableau -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase">
                    <th class="py-3 px-2">Référence / Titre</th>
                    <th class="py-3 px-2">Service</th>
                    <th class="py-3 px-2">Boîte</th>
                    <th class="py-3 px-2">Date d'ouverture</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 text-xs">
                <tr v-for="dossier in filteredDossiers" :key="dossier.id" class="hover:bg-slate-50/80 transition-colors">

  <!-- Référence / Titre -->
  <td class="py-3 px-2">
    <p class="font-bold text-slate-800">{{ dossier.titre || dossier.nom }}</p>
    <p class="text-[10px] text-slate-400 font-mono">{{ dossier.numero_reference || dossier.code }}</p>
  </td>

  <!-- Service -->
  <td class="py-3 px-2">
    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">
      {{ dossier.service?.nom || dossier.boite?.casier?.service?.nom || 'N/A' }}
    </span>
  </td>

  <!-- Boîte -->
  <td class="py-3 px-2 font-medium text-slate-600">
  <template v-if="dossier.boite">
    <span v-if="dossier.boite.casier && dossier.boite.nom !== dossier.boite.casier.nom">
      {{ dossier.boite.casier.nom }} — {{ dossier.boite.nom || dossier.boite.numero_boite || dossier.boite.numero || dossier.boite.code }}
    </span>
    <span v-else>
      {{ dossier.boite.casier?.nom || dossier.boite.nom || dossier.boite.numero_boite || dossier.boite.numero || dossier.boite.code }}
    </span>
  </template>
  <template v-else>N/A</template>
</td>

  <!-- Date -->
  <td class="py-3 px-2 text-slate-500">
    {{ formatDate(dossier.created_at || dossier.date_ouverture) }}
  </td>

</tr>
                <tr v-if="!filteredDossiers.length">
                    <td colspan="4" class="py-8 text-center text-slate-400 text-xs">
                        Aucun dossier ne correspond à votre recherche.
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- Pagination des Dossiers -->
<div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
    <span>
        Affichage de <strong class="font-semibold text-slate-700">{{ filteredDossiers.length }}</strong> dossier(s)
    </span>
    
    <div class="flex items-center gap-2">
        <button 
            type="button"
            disabled
            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-400 cursor-not-allowed opacity-60"
        >
            Précédent
        </button>
        <span class="px-3 py-1.5 rounded-lg bg-blue-600 text-white font-semibold">1</span>
        <button 
            type="button"
            disabled
            class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-400 cursor-not-allowed opacity-60"
        >
            Suivant
        </button>
    </div>
</div>
    </div>
</div>
        </main>

        <!-- Fenêtre Modale de Création de Compte -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-lg font-bold text-slate-900">Nouveau Compte</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
                </div>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nom Complet</label>
                        <input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Adresse Email</label>
                        <input v-model="form.email" type="email" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Mot de Passe</label>
                        <input v-model="form.password" type="password" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Rôle</label>
                        <select v-model="form.role" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="agent">Agent de service</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>

                    <div v-if="form.role === 'agent'">
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Service Attribué</label>
                        <select v-model="form.service_id" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required>
                            <option value="" disabled>Sélectionner un service</option>
                            <option v-for="service in services" :key="service.id" :value="service.id">
                                {{ service.nom }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800">
                            Annuler
                        </button>
                        <button :disabled="form.processing" type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-xl text-sm transition">
                            Créer le compte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modale de Consultation de Dossier -->
<div v-if="showDossierModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
        <!-- En-tête -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                    {{ selectedDossier?.reference }}
                </span>
                <h3 class="text-lg font-extrabold text-slate-800 mt-1">
                    {{ selectedDossier?.titre }}
                </h3>
            </div>
            <button 
                @click="showDossierModal = false" 
                class="w-8 h-8 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors"
            >
                ✕
            </button>
        </div>

        <!-- Informations Générales -->
        <div class="grid grid-cols-2 gap-4 my-5 text-xs">
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <p class="text-slate-400 font-medium">Service d'origine</p>
                <p class="font-bold text-slate-700 mt-0.5">{{ selectedDossier?.boite?.service?.nom || 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <p class="text-slate-400 font-medium">Boîte d'archivage</p>
                <p class="font-bold text-slate-700 mt-0.5">{{ selectedDossier?.boite?.nom || 'N/A' }}</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <p class="text-slate-400 font-medium">Date d'ouverture</p>
                <p class="font-bold text-slate-700 mt-0.5">{{ selectedDossier?.date_ouverture || 'Non renseignée' }}</p>
            </div>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <p class="text-slate-400 font-medium">Créé par</p>
                <p class="font-bold text-slate-700 mt-0.5">{{ selectedDossier?.user?.name || 'Agent' }}</p>
            </div>
        </div>

        <!-- Description -->
        <div class="mb-5" v-if="selectedDossier?.description">
            <h4 class="text-xs font-bold text-slate-700 mb-1">Description / Notes</h4>
            <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                {{ selectedDossier.description }}
            </p>
        </div>

        <!-- Liste des Fichiers / Documents -->
        <div>
            <h4 class="text-xs font-bold text-slate-700 mb-2">Pièces Jointes & Documents</h4>
            <div v-if="selectedDossier?.documents && selectedDossier.documents.length" class="space-y-2">
                <div 
                    v-for="doc in selectedDossier.documents" 
                    :key="doc.id" 
                    class="flex items-center justify-between p-2.5 rounded-xl border border-slate-100 hover:border-blue-200 bg-slate-50/50 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-base">📄</span>
                        <div>
                            <p class="text-xs font-bold text-slate-700">{{ doc.nom || 'Document' }}</p>
                            <p class="text-[10px] text-slate-400">{{ doc.taille || 'PDF/Scan' }}</p>
                        </div>
                    </div>
                    <a 
                        :href="doc.url || '#'" 
                        target="_blank" 
                        class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-lg transition-colors"
                    >
                        Télécharger ⬇️
                    </a>
                </div>
            </div>
            <div v-else class="text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p class="text-xs text-slate-400">Aucun fichier numérique joint à ce dossier.</p>
            </div>
        </div>

        <!-- Pied de Modale -->
        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
            <button 
                @click="showDossierModal = false" 
                class="px-4 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-xs font-bold transition-colors"
            >
                Fermer
            </button>
        </div>
    </div>

    <!-- Pop-up de consultation du dossier -->
<div 
    v-if="showDossierModal && selectedDossier" 
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
        <!-- Titre de la pop-up -->
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="font-bold text-base">Détails du Dossier</h3>
            <button @click="showDossierModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <!-- Informations du dossier -->
        <div class="p-6 space-y-4">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase">Référence & Titre</p>
                <p class="text-base font-bold text-slate-800">{{ selectedDossier.reference }} - {{ selectedDossier.titre }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">Service originel</p>
                    <p class="text-sm font-semibold text-slate-700">{{ selectedDossier.boite?.service?.nom || 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">Numéro de Boîte</p>
                    <p class="text-sm font-semibold text-slate-700">{{ selectedDossier.boite?.numero || 'N/A' }}</p>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Description / Contenu</p>
                <p class="text-sm text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    {{ selectedDossier.description || 'Aucune description disponible.' }}
                </p>
            </div>
        </div>

        <!-- Bouton de fermeture -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button 
                @click="showDossierModal = false" 
                class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 rounded-xl"
            >
                Fermer
            </button>
        </div>
    </div>
</div>

</div>

<!-- Modal Édition Utilisateur -->
<div 
    v-if="showEditUserModal && editingUser" 
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
        <!-- En-tête -->
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="font-bold text-base">Modifier l'Utilisateur</h3>
            <button @click="showEditUserModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <!-- Formulaire d'édition -->
        <form @submit.prevent="submitEditUser" class="p-6 space-y-4">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase">Utilisateur</p>
                <p class="text-sm font-semibold text-slate-800">{{ editingUser.email }}</p>
            </div>

            <!-- Choix du Rôle -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Rôle</label>
                <select 
                    v-model="editUserForm.role"
                    class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="agent">Agent</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>

            <!-- Choix du Service (si rôle Agent) -->
            <div v-if="editUserForm.role === 'agent'">
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Service Municipal</label>
                <select 
                    v-model="editUserForm.service_id"
                    class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="" disabled>Sélectionner un service</option>
                    <option v-for="service in props.services" :key="service.id" :value="service.id">
                        {{ service.nom }}
                    </option>
                </select>
            </div>

            <!-- Boutons de soumission -->
            <div class="pt-4 flex justify-end gap-2 border-t border-slate-100">
                <button 
                    type="button"
                    @click="showEditUserModal = false" 
                    class="px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 rounded-xl"
                >
                    Annuler
                </button>
                <button 
                    type="submit" 
                    :disabled="editUserForm.processing"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors disabled:opacity-50"
                >
                    {{ editUserForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                </button>
            </div>
        </form>
    </div>
</div>

</template>