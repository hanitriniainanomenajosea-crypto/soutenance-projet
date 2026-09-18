<script setup>
import { onMounted, ref, computed, watch, nextTick } from 'vue';
import { useForm, usePage, Head, Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import Sidebar from '@/Components/Sidebar.vue';
import html2pdf  from 'html2pdf.js';
const isCollapsed = ref(false)

const props = defineProps({
    services: Array,
    users: Array,
    dossiers: Array,
    stats: Object,
    logs: Array,
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
const roleFilter = ref('');
const logSearchQuery = ref('');
const actionFilter = ref('');

// Calcul dynamique des dossiers filtrés
const filteredDossiers = computed(() => {
  if (!props.dossiers) return [];

  return props.dossiers.filter(dossier => {
    // 1. Filtre par recherche textuelle (titre / référence)
    const query = searchQuery.value ? searchQuery.value.toLowerCase().trim() : '';
    const matchesSearch = !query || 
      (dossier.titre && dossier.titre.toLowerCase().includes(query)) ||
      (dossier.numero_reference && dossier.numero_reference.toLowerCase().includes(query)) ||
      (dossier.reference && dossier.reference.toLowerCase().includes(query));

    // 2. Filtre par Service
    const selected = selectedService.value;

    // Si aucun service n'est sélectionné ou si "Tous les services" est choisi
    const isAllSelected = !selected || selected === '' || selected === 'Tous les services' || selected === 'all';

    if (isAllSelected) {
      return matchesSearch;
    }

    // Récupération des IDs et Noms du service du dossier pour comparaison
    const dossierServiceId = dossier.service_id || dossier.service?.id || dossier.boite?.service_id || dossier.boite?.casier?.service_id;
    const dossierServiceName = (dossier.service?.nom || dossier.service_nom || dossier.boite?.casier?.service?.nom || '').toLowerCase().trim();
    const filterValue = String(selected).toLowerCase().trim();

    // Comparaison souple (par ID numérique OU par nom de service)
    const matchesService = 
      String(dossierServiceId) === filterValue || 
      dossierServiceName === filterValue ||
      (dossierServiceName && dossierServiceName.includes(filterValue));

    return matchesSearch && matchesService;
  });
});

const filteredLogs = computed(() => {
  if (!props.logs) return [];
  return props.logs.filter(log => {
    const query = logSearchQuery.value.toLowerCase().trim();
    const userMatch = (log.user_email || log.user?.email || '').toLowerCase().includes(query);
    const descMatch = (log.description || log.details || '').toLowerCase().includes(query);
    const ipMatch = (log.ip_address || log.ip || '').toLowerCase().includes(query);
    const matchesSearch = !query || userMatch || descMatch || ipMatch;

    const matchesAction = !actionFilter.value || log.action === actionFilter.value;

    return matchesSearch && matchesAction;
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

// Filtre dynamique des utilisateurs
const filteredUsers = computed(() => {
  const list = props.users || [];

  return list.filter(user => {
    // Recherche par nom ou email
    const query = searchQuery.value ? searchQuery.value.toLowerCase().trim() : '';
    const nameMatch = user.name ? user.name.toLowerCase().includes(query) : false;
    const emailMatch = user.email ? user.email.toLowerCase().includes(query) : false;
    const matchesSearch = !query || nameMatch || emailMatch;

    // Filtre par rôle (admin / agent)
    const matchesRole = !roleFilter.value || user.role === roleFilter.value;

    return matchesSearch && matchesRole;
  });
});

const deleteUser = (user) => {
    if (confirm(`Voulez-vous vraiment supprimer le compte de ${user.email} ?`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
};

Chart.register(...registerables);

let chartInstance = null;
const chartCanvas = ref(null);
const barChartCanvas = ref(null);
let barChartInstance = null;
// Données calculées
const chartData = computed(() => {
  // Les couleurs exactes dans l'ordre de votre légende (Bleu, Violet, Jaune, Vert)
  const palette = ['#3b82f6', '#8b5cf6', '#eab308', '#10b981']; 

  const labels = [];
  const data = [];
  const backgroundColors = [];

  // On parcourt les services dans leur ordre officiel
  if (props.services && props.services.length > 0) {
    props.services.forEach((service, index) => {
      // 1. Ajouter le nom exact du service
      labels.push(service.nom);
      
      // 2. Assigner la couleur dans le même ordre que la légende
      backgroundColors.push(palette[index % palette.length]);

      // 3. Compter les dossiers pour CE service précis
      const count = (props.dossiers || []).filter(dossier => {
        const serviceNom = dossier.boite?.casier?.service?.nom 
                        || dossier.service?.nom 
                        || dossier.boite?.service?.nom;
        return serviceNom === service.nom;
      }).length;

      data.push(count);
    });
  }

  return {
    labels: labels,
    datasets: [{
      label: 'Nombre de dossiers',
      data: data,
      backgroundColor: backgroundColors,
      borderWidth: 0,
      hoverOffset: 6
    }]
  };
});
const activeTab = ref('dashboard')



const renderChart = async () => {
  await nextTick();
  setTimeout(() => {
    if (!chartCanvas.value) return;
    const ctx = chartCanvas.value.getContext('2d');
    if (!ctx) return;

    if (chartInstance) {
      chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: chartData.value,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        cutout: '75%'
      }
    });
  }, 100);
};


watch(activeTab, (newTab) => {
  if (newTab === 'dashboard' || newTab === 'dashbord') {
    renderChart();
  }
});

onMounted(() => {
  setTimeout(() => {
    renderChart();      
    renderBarChart();  
  }, 100);
});

watch(() => props.dossiers, () => {
  renderChart();
  renderBarChart();
}, { deep: true });

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getActionBadgeStyle = (action) => {
  if (!action) return 'bg-slate-100 text-slate-600';
  const act = action.toLowerCase();
  
  if (act.includes('création') || act.includes('créer')) {
    return 'bg-blue-50 text-blue-600 border border-blue-100';
  } else if (act.includes('connexion')) {
    return 'bg-purple-50 text-purple-600 border border-purple-100';
  } else if (act.includes('modification')) {
    return 'bg-amber-50 text-amber-700 border border-amber-100';
  } else if (act.includes('suppression')) {
    return 'bg-rose-50 text-rose-600 border border-rose-100';
  }
  return 'bg-slate-100 text-slate-600';
};

const refreshLogs = () => {
  router.reload({ only: ['logs'] });
};

const period = ref('mensuel');

const barChartData = computed(() => {
  if (period.value === 'trimestriel') {
    return {
      labels: ['T1 (Jan-Mar)', 'T2 (Avr-Juin)', 'T3 (Juil-Sept)', 'T4 (Oct-Déc)'],
      data: [30, 45, 48, props.dossiers?.length || 11]
    };
  }
  return {
    labels: ['Mai', 'Juin', 'Juil', 'Août', 'Sept (En cours)'],
    data: [12, 19, 14, 22, props.dossiers?.length || 8]
  };
});

const renderBarChart = async () => {
  await nextTick();
  if (!barChartCanvas.value) return;

  if (barChartInstance) {
    barChartInstance.destroy();
  }

  const ctx = barChartCanvas.value.getContext('2d');
  barChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: barChartData.value.labels, // Utilise les labels dynamiques
      datasets: [{
        label: 'Entrées',
        data: barChartData.value.data,     // Utilise les données dynamiques
        backgroundColor: '#3b82f6',
        borderRadius: 6,
        barThickness: 22,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { enabled: true }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#94a3b8', font: { size: 11 } }
        },
        y: {
          min: 0,
          max: 50,
          ticks: { stepSize: 10, color: '#94a3b8', font: { size: 11 } },
          grid: { color: '#f1f5f9' }
        }
      }
    }
  });
};
const exportPDF = async () => {
  const element = document.getElementById('pdf-content')
  if (!element) return

  const container = element.parentElement
  container.style.display = 'block'

  const options = {
    margin:       [10, 10, 10, 10],
    filename:     `Fiche_Archive_${selectedDossier.value?.reference || 'dossier'}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, logging: false, useCORS: true },
    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
    pagebreak:    { mode: 'avoid-all' }
  }

  await html2pdf().set(options).from(element).save()

  container.style.display = 'none'
}
</script>

<template>
    <Head title="Tableau de Bord Admin" />

    <div class="min-h-screen bg-slate-100 font-sans">
    <Sidebar v-model:isCollapsed="isCollapsed" 
    v-model:activeTab="activeTab"
    />

    <main :class="['flex-1 transition-all duration-300 pt-2 pb-8 px-6 space-y-6', isCollapsed ? 'ml-20' : 'ml-64']">
        
        <!-- Barre de Navigation Supérieure -->
        <nav class="flex justify-end items-center mb-1 relative z-50 ">


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
        <main class="flex-1 w-full p-8 pt-1 space-y-6">
            
            <!-- Bandeau de Bienvenue -->
            <div v-if="activeTab === 'dashboard'" class="bg-white p-10 mt-1 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Tableau de Bord Global</h2>
                    <p class="text-slate-500 text-sm mt-1">Supervision de l'ensemble des services, utilisateurs et archives.</p>
                </div>
                <span class="inline-flex items-center text-xs font-semibold px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full">
                    <span class="w-2 h-2 mr-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Système actif
                </span>
            </div>
            
            
            <!-- 1. CARTES DE STATISTIQUES -->
    <div  v-if="activeTab === 'dashboard'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      
      <!-- Services -->
      <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex justify-between items-start">
        <div>
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SERVICES</span>
          <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ stats?.services || 0 }}</p>
          <p class="text-xs text-emerald-600 font-semibold mt-2 flex items-center gap-1">
            <span>✓</span> Tous opérationnels
          </p>
        </div>
        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
          🏛️
        </div>
      </div>

      <!-- Utilisateurs -->
      <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex justify-between items-start">
        <div>
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">UTILISATEURS</span>
          <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ stats?.users || 0 }}</p>
          <p class="text-xs text-blue-600 font-semibold mt-2 flex items-center gap-1">
            <span>👥</span> Admins & Agents
          </p>
        </div>
        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
          👤
        </div>
      </div>

      <!-- Boîtes -->
      <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex justify-between items-start">
        <div>
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">BOÎTES</span>
          <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ stats?.boites || 0 }}</p>
          <p class="text-xs text-amber-600 font-semibold mt-2 flex items-center gap-1">
            <span>📦</span> Casiers répertoriés
          </p>
        </div>
        <div class="p-3 bg-amber-50 text-amber-600 rounded-lg">
          📦
        </div>
      </div>

      <!-- Dossiers -->
      <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex justify-between items-start">
        <div>
          <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">DOSSIERS</span>
          <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ stats?.dossiers || 0 }}</p>
          <p class="text-xs text-emerald-600 font-semibold mt-2 flex items-center gap-1">
            <span>📂</span> Numérisés au total
          </p>
        </div>
        <div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
          📂
        </div>
      </div>

    </div>

<div v-if="activeTab === 'users'" class="space-y-6">

  <!-- 1. EN-TÊTE : Titre + Sous-titre + Badge + Bouton Nouveau Compte -->
  <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h2 class="text-2xl font-bold text-slate-800">Utilisateurs Enregistrés</h2>
      <p class="text-sm text-slate-500 mt-1">Gestion des accès, attributions des services municipaux et gestion des rôles d'agents.</p>
      <span class="inline-flex items-center gap-1.5 px-3 py-1 mt-3 rounded-full text-xs font-semibold bg-blue-50 text-blue-600">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        {{ users?.length || 0 }} comptes actifs
      </span>
    </div>
  </div>

  <!-- 2. CARTES STATISTIQUES (Total, Agents, Admins) -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Total -->
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">TOTAL UTILISATEURS</p>
        <p class="text-3xl font-black text-slate-800 mt-1">{{ users?.length || 0 }}</p>
        <p class="text-xs text-slate-400 mt-1">Accès enregistrés</p>
      </div>
      <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
    </div>

    <!-- Agents -->
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">AGENTS MUNICIPAUX</p>
        <p class="text-3xl font-black text-slate-800 mt-1">
          {{ users?.filter(u => u.role === 'agent').length || 0 }}
        </p>
        <p class="text-xs font-medium text-emerald-600 mt-1 flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Opérationnels
        </p>
      </div>
      <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
      </div>
    </div>

    <!-- Admins -->
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">ADMINISTRATEURS</p>
        <p class="text-3xl font-black text-slate-800 mt-1">
          {{ users?.filter(u => u.role === 'admin').length || 0 }}
        </p>
        <p class="text-xs font-medium text-purple-600 mt-1 flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          Superviseur
        </p>
      </div>
      <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
    </div>
  </div>

  <!-- 3. TABLEAU DES UTILISATEURS AVEC BARRE DE RECHERCHE -->
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    
    <!-- Zone Filtres -->
    <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="relative flex-1 max-w-md">
        <svg class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher par email ou nom..." 
          class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
        />
      </div>

      <div class="flex items-center gap-3">
        <select v-model="roleFilter" class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
          <option value="">Tous les rôles</option>
          <option value="admin">Administrateurs</option>
          <option value="agent">Agents</option>
        </select>
        <span class="px-3 py-2 bg-slate-100 text-slate-600 text-xs font-semibold rounded-xl">
          {{ filteredUsers.length }} sur {{ users?.length || 0 }} utilisateurs
        </span>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
            <th class="py-4 px-6">IDENTIFIANT / NOM</th>
            <th class="py-4 px-6">EMAIL</th>
            <th class="py-4 px-6">RÔLE</th>
            <th class="py-4 px-6">SERVICE MUNICIPALE</th>
            <th class="py-4 px-6 text-right">ACTIONS</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/60 transition">
            <!-- Avatar + Nom -->
            <td class="py-4 px-6 font-semibold text-slate-800">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs shadow-sm"
                     :class="user.role === 'admin' ? 'bg-purple-600' : 'bg-blue-600'">
                  {{ (user.name || user.email).charAt(0).toUpperCase() }}
                </div>
                <span>{{ user.name || 'Agent' }}</span>
              </div>
            </td>

            <!-- Email -->
            <td class="py-4 px-6 text-slate-500 font-mono text-xs">{{ user.email }}</td>

            <!-- Rôle Badge -->
            <td class="py-4 px-6">
              <span class="px-2.5 py-1 rounded-lg text-xs font-semibold"
                    :class="user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                {{ user.role }}
              </span>
            </td>

            <!-- Service Municipal -->
            <td class="py-4 px-6 text-slate-600">
              {{ user.service?.nom || '—' }}
            </td>

            <!-- Actions -->
            <td class="py-4 px-6 text-right">
  <div class="flex items-center justify-end gap-2">
    <!-- Bouton Modifier -->
    <button 
      type="button" 
      @click="openEditUser(user)" 
      class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-xl transition border border-amber-200/60 shadow-sm"
      title="Modifier"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
      </svg>
    </button>

    <!-- Bouton Supprimer -->
    <button 
      type="button" 
      @click="deleteUser(user)" 
      class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-xl transition border border-rose-200/60 shadow-sm"
      title="Supprimer"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
      </svg>
    </button>
  </div>
</td>
          </tr>

          <!-- Si aucun utilisateur n'est trouvé -->
          <tr v-if="filteredUsers.length === 0">
            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
              Aucun utilisateur trouvé.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
      <span>Affichage de {{ filteredUsers.length }} utilisateur(s)</span>
      <div class="flex items-center gap-1">
        <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-400 cursor-not-allowed">Précédent</button>
        <button class="px-3 py-1.5 rounded-lg bg-blue-600 text-white font-semibold">1</button>
        <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Suivant</button>
      </div>
    </div>

  </div>
</div>

            <!-- SECTION GRAPHIQUES -->
<div v-if="activeTab === 'dashboard'" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

  <!-- 1. CARTE GAUCHE : Répartition des Archives (Donut) -->
  <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-base font-bold text-slate-800">Répartition des Archives</h3>
          <p class="text-xs text-slate-400 mt-0.5">Volume de dossiers par service municipal</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg">Dynamique</span>
      </div>

      <!-- Graphique Doughnut avec chiffre au centre -->
      <div class="relative w-44 h-44 mx-auto my-4 flex items-center justify-center">
        <canvas ref="chartCanvas"></canvas>
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <span class="text-2xl font-black text-slate-800">{{ dossiers?.length ?? 8 }}</span>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Dossiers</span>
        </div>
      </div>

      <!-- Légende des services dynamique -->
<div class="grid grid-cols-2 gap-2 mt-4 pt-2 text-[11px]">
  <div 
    v-for="(label, index) in chartData.labels" 
    :key="label" 
    class="flex items-center gap-1.5 text-slate-600"
  >
    <span 
      class="w-2.5 h-2.5 rounded-full inline-block shrink-0" 
      :style="{ backgroundColor: chartData.datasets[0]?.backgroundColor[index] }"
    ></span>
    <span class="truncate">{{ label }}</span>
  </div>
</div>
    </div>

    <!-- Bas de carte Donut -->
    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
      <span>Total des services enregistrés</span>
      <span class="font-bold text-slate-800">{{ services?.length ?? 4 }} Services</span>
    </div>
  </div>

  <!-- 2. CARTE DROITE : Activité d'Archivage (Graphique en Bâtons) -->
<div class="lg:col-span-2 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-slate-800">Activité d'Archivage (2026)</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          {{ period === 'mensuel' ? 'Entrées de nouveaux dossiers par mois' : 'Entrées de nouveaux dossiers par trimestre' }}
        </p>
      </div>

      <!-- Boutons de filtre de période dynamiques -->
      <div class="inline-flex bg-slate-100 p-1 rounded-xl text-xs font-semibold text-slate-600">
        <button 
          @click="period = 'mensuel'; renderBarChart()" 
          :class="period === 'mensuel' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-3 py-1 rounded-lg transition-all"
        >
          Mensuel
        </button>
        <button 
          @click="period = 'trimestriel'; renderBarChart()" 
          :class="period === 'trimestriel' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800'"
          class="px-3 py-1 rounded-lg transition-all"
        >
          Trimestriel
        </button>
      </div>
    </div>

    <!-- Emplacement du graphique en bâtons -->
    <div class="h-56 relative w-full mt-4">
      <canvas ref="barChartCanvas"></canvas>
    </div>
  </div>

  <!-- Bas de carte Activité -->
  <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-medium">
    <div class="flex items-center gap-1 text-emerald-600 font-semibold">
      <span>📈</span>
      <span>+12% par rapport au mois dernier</span>
    </div>
    <span class="text-slate-400">Mise à jour: Aujourd'hui 10:55</span>
  </div>
</div>

</div>

            <!-- 1. En-tête avec Filtres et Recherche -->
    <div 
      v-if="activeTab === 'dashboard' || activeTab === 'dossiers'" 
      class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden flex flex-col"
    >
      <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-bold text-slate-800">Tous les Dossiers d'Archives</h2>
          <p class="text-xs text-slate-400">Consultez et recherchez parmi l'ensemble des dossiers archivés.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Select Filtre Service -->
          <select 
            v-model="selectedService" 
            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          >
            <option value="">Tous les services</option>
            <option v-for="service in services" :key="service.id" :value="service.nom">
              {{ service.nom }}
            </option>
          </select>

          <!-- Input Recherche -->
          <div class="relative">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Rechercher par titre..." 
              class="pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20 w-48 focus:w-64 transition-all"
            />
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>

          <!-- Bouton Imprimer / Compteur -->
          <button 
            @click="exportPDF" 
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-2 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimer / Exporter (PDF)
          </button>
          
          <span class="px-3 py-1.5 bg-blue-600 text-white rounded-xl text-xs font-bold">
            {{ filteredDossiers.length }} / {{ dossiers.length }}
          </span>
        </div>
      </div>

      <!-- 2. Table de Données -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px] tracking-wider bg-slate-50/50">
              <th class="py-3.5 px-4">TITRE DU DOSSIER</th>
              <th class="py-3.5 px-4">RÉFÉRENCE</th>
              <th class="py-3.5 px-4">SERVICE</th>
              <th class="py-3.5 px-4">BOÎTE / CASIER</th>
              <th class="py-3.5 px-4">DATE D'OUVERTURE</th>
              <th class="py-3.5 px-4 text-center">VOIR</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-slate-700">
            <tr v-for="dossier in filteredDossiers" :key="dossier.id" class="hover:bg-slate-50/80 transition">
              
              <!-- 1. Titre -->
              <td class="py-3.5 px-4 font-semibold text-slate-800">
                {{ dossier.titre || 'Sans titre' }}
              </td>

              <!-- 2. Référence -->
              <td class="py-3.5 px-4 text-slate-400 font-mono">
                {{ dossier.numero_reference || dossier.reference || dossier.code || '-' }}
              </td>

              <!-- 3. Service -->
              <td class="py-3.5 px-4">
                <span
                  :class="{
                    'bg-emerald-100 text-emerald-800 border border-emerald-200': (dossier.service?.nom || dossier.boite?.casier?.service?.nom) === 'Service Finances et Comptabilité',
                    'bg-purple-100 text-purple-800 border border-purple-200': (dossier.service?.nom || dossier.boite?.casier?.service?.nom) === 'Service Ressources Humaines',
                    'bg-amber-100 text-amber-800 border border-amber-200': (dossier.service?.nom || dossier.boite?.casier?.service?.nom) === 'Service État Civil',
                    'bg-blue-100 text-blue-800 border border-blue-200': (dossier.service?.nom || dossier.boite?.casier?.service?.nom) === 'Service Urbanisme et Foncier'
                  }"
                  class="px-2.5 py-1 rounded-lg text-[11px] font-semibold inline-block bg-slate-100 text-slate-700"
                >
                  {{ dossier.service?.nom || dossier.boite?.casier?.service?.nom || 'N/A' }}
                </span>
              </td>

              <!-- 4. Boîte / Casier -->
              <td class="py-3.5 px-4 text-slate-700 font-medium">
                <template v-if="dossier.boite">
                  <span v-if="dossier.boite.casier && dossier.boite.nom != dossier.boite.casier.nom">
                    {{ dossier.boite.casier.nom }} — {{ dossier.boite.nom || dossier.boite.numero_boite || dossier.boite.numero || 'Boîte' }}
                  </span>
                  <span v-else>
                    {{ dossier.boite.casier?.nom || dossier.boite.nom || dossier.boite.numero_boite || dossier.boite.numero || 'Boîte' }}
                  </span>
                </template>
                <template v-else>N/A</template>
              </td>

              <!-- 5. Date d'ouverture -->
              <td class="py-3.5 px-4 text-slate-500 font-medium">
                {{ formatDate(dossier.created_at || dossier.date_ouverture) }}
              </td>

              <!-- 6. Action Voir -->
              <td class="py-3.5 px-4 text-center">
                <button
                  @click="openDossier(dossier)"
                  class="text-blue-600 hover:text-blue-800 font-bold p-1"
                  title="Voir le dossier"
                >
                  <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </td>
            </tr>

            <!-- Message en cas de résultat vide -->
            <tr v-if="!filteredDossiers.length">
              <td colspan="6" class="py-12 text-center text-slate-400 text-xs font-medium">
                Aucun dossier ne correspond à votre recherche.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 3. Pagination des Dossiers -->
      <div class="px-6 py-3.5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
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

       <div v-if="activeTab === 'logs'" class="space-y-6">

  <!-- 1. BANNIÈRE SUPÉRIEURE -->
  <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h3 class="text-2xl font-bold text-slate-800 tracking-tight">Historique des Activités Récentes</h3>
      <p class="text-sm text-slate-400 mt-1">Journal de suivi des actions effectuées par les utilisateurs du système.</p>
      <div class="mt-3">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-600 border border-blue-100/60">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ logs ? logs.length : 0 }} action(s) récente(s)
        </span>
      </div>
    </div>
    <button @click="refreshLogs" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-2xl transition">
      <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      Actualiser le journal
    </button>
  </div>

  <!-- 2. CARTES KPI (STATISTIQUES) -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Carte 1 : Total -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
      <div>
        <p class="text-xs font-bold tracking-wider text-slate-400 uppercase">Total des événements</p>
        <h4 class="text-3xl font-extrabold text-slate-800 mt-2">{{ logs ? logs.length : 0 }}</h4>
        <p class="text-xs text-slate-400 mt-1">Journal de traçabilité</p>
      </div>
      <div class="p-3.5 bg-blue-50 rounded-2xl text-blue-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
      </div>
    </div>

    <!-- Carte 2 : Créations -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
      <div>
        <p class="text-xs font-bold tracking-wider text-slate-400 uppercase">Créations & Ajouts</p>
        <h4 class="text-3xl font-extrabold text-slate-800 mt-2">
          {{ logs ? logs.filter(l => l.action && l.action.toLowerCase().includes('création')).length : 0 }}
        </h4>
        <p class="text-xs text-emerald-600 font-semibold mt-1 flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Nouveaux dossiers
        </p>
      </div>
      <div class="p-3.5 bg-emerald-50 rounded-2xl text-emerald-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
    </div>

    <!-- Carte 3 : Dernière Connexion -->
    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
      <div>
        <p class="text-xs font-bold tracking-wider text-slate-400 uppercase">Dernière connexion</p>
        <h4 class="text-xl font-bold text-slate-800 mt-2">Aujourd'hui</h4>
        <p class="text-xs text-purple-600 font-semibold mt-1 flex items-center gap-1">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          IP: 127.0.0.1
        </p>
      </div>
      <div class="p-3.5 bg-purple-50 rounded-2xl text-purple-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </div>
    </div>
  </div>

  <!-- 3. BARRE DE RECHERCHE ET FILTRE -->
  <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="relative w-full md:w-96">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </span>
      <input 
        v-model="logSearchQuery" 
        type="text" 
        placeholder="Rechercher par utilisateur, description, IP..." 
        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200/80 rounded-2xl text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
      />
    </div>

    <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
      <select v-model="actionFilter" class="bg-slate-50 border border-slate-200/80 rounded-2xl px-4 py-2.5 text-xs text-slate-600 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        <option value="">Toutes les actions</option>
        <option value="Création Dossier">Création Dossier</option>
        <option value="Connexion">Connexion</option>
        <option value="Modification">Modification</option>
        <option value="Suppression">Suppression</option>
      </select>

      <span class="px-4 py-2.5 bg-slate-100 text-slate-600 text-xs font-bold rounded-2xl">
        {{ filteredLogs.length }} sur {{ logs ? logs.length : 0 }} enregistrements
      </span>
    </div>
  </div>

  <!-- 4. TABLEAU DU JOURNAL -->
  <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-100 text-[11px] font-extrabold text-slate-400 uppercase tracking-wider bg-slate-50/50">
            <th class="py-4 px-6">UTILISATEUR</th>
            <th class="py-4 px-6">ACTION</th>
            <th class="py-4 px-6">DÉTAILS / DESCRIPTION</th>
            <th class="py-4 px-6">SERVICE</th>
            <th class="py-4 px-6 text-right">DATE & HEURE</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-xs">
          <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50/60 transition">
            <!-- Utilisateur avec l'icône -->
            <td class="py-4 px-6 font-medium text-slate-700">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>{{ log.user_email || log.user?.email || 'admin@maharanga.mg' }}</span>
              </div>
            </td>
            
            <!-- Badge d'action -->
            <td class="py-4 px-6">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold" :class="getActionBadgeStyle(log.action)">
                {{ log.action }}
              </span>
            </td>

            <!-- Description -->
            <td class="py-4 px-6 text-slate-800 font-medium">
              {{ log.description || log.details }}
            </td>

            <!-- Service -->
            <td class="py-3.5 px-4">
  <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg font-medium text-[11px]">
    {{ log.user?.service?.nom || log.service_name || log.service || 'Non assigné' }}
  </span>
</td>

            <!-- Date -->
            <td class="py-4 px-6 text-right text-slate-500 whitespace-nowrap">
              {{ formatDate(log.created_at) }}
            </td>
          </tr>

          <!-- Message si aucun log -->
          <tr v-if="!filteredLogs || filteredLogs.length === 0">
            <td colspan="5" class="py-8 text-center text-slate-400 font-medium">
              Aucune activité enregistrée.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- 5. PAGINATION (Pied du tableau) -->
    <div class="p-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 bg-slate-50/30">
      <span>Affichage de {{ filteredLogs.length }} action(s)</span>
      <div class="flex items-center gap-2">
        <button disabled class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-400 bg-white cursor-not-allowed opacity-60">
          Précédent
        </button>
        <button class="px-3 py-1.5 rounded-xl bg-blue-600 text-white font-bold shadow-sm">
          1
        </button>
        <button disabled class="px-3 py-1.5 rounded-xl border border-slate-200 text-slate-400 bg-white cursor-not-allowed opacity-60">
          Suivant
        </button>
      </div>
    </div>
  </div>

</div>

        </main>
        </main>
        
        <!-- Fenêtre Modale de Création de Compte -->
<div v-if="showModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-bold text-slate-900">Nouveau Compte</h3>
            <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>
        
        <form @submit.prevent="submit" class="space-y-4">
            <!-- Nom Complet -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Nom Complet</label>
                <input v-model="form.name" type="text" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                <p v-if="form.errors?.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
            </div>

            <!-- Adresse Email -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Adresse Email</label>
                <input v-model="form.email" type="email" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                <p v-if="form.errors?.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
            </div>

            <!-- Mot de Passe -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Mot de Passe</label>
                <input v-model="form.password" type="password" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" required />
                <p v-if="form.errors?.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
            </div>

            <!-- Rôle -->
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Rôle</label>
                <select v-model="form.role" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="agent">Agent de service</option>
                    <option value="admin">Administrateur</option>
                    <option value="viewer">Maire / Superviseur (Lecture seule)</option>
                </select>
            </div>

            <!-- Service Attribué (uniquement pour les agents) -->
            <div v-if="form.role === 'agent'">
                <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Service Attribué</label>
                <select v-model="form.service_id" class="w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" :required="form.role === 'agent'">
                    <option value="" disabled>Sélectionner un service</option>
                    <option v-for="service in services" :key="service.id" :value="service.id">
                        {{ service.nom }}
                    </option>
                </select>
                <p v-if="form.errors?.service_id" class="text-red-500 text-xs mt-1">{{ form.errors.service_id }}</p>
            </div>
                
            <div class="flex justify-end space-x-3 pt-4 border-t">
                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800">
                    Annuler
                </button>
                <button type="submit" :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-xl text-sm transition flex items-center gap-2">
                    <span v-if="form.processing">Création...</span>
                    <span v-else>Créer le compte</span>
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

    <!-- Pop-up de consultation du dossier (Nouveau Design) -->
<div 
  v-if="showDossierModal && selectedDossier"
  class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4"
>
  <div 
    class="bg-slate-100 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200"
  >
    <!-- En-tête bleu foncé avec icône dossier et référence -->
    <div class="px-6 py-5 bg-[#1b253b] text-white flex items-start justify-between">
      <div class="flex items-center gap-4">
        <!-- Icône Dossier Bleue -->
        <div class="p-3 bg-blue-600/30 text-blue-400 rounded-xl flex items-center justify-center">
          <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
            <path d="M19.5 21a3 3 0 003-3v-8a3 3 0 00-3-3h-7.69l-1.92-2.112A2 2 0 008.4 4H4.5a3 3 0 00-3 3v11a3 3 0 003 3h15z" />
          </svg>
        </div>
        <div>
          <h3 class="font-bold text-lg leading-snug text-white">
            {{ selectedDossier.titre || selectedDossier.nom || 'Pieces justificatives des dépenses' }}
          </h3>
          <p class="text-xs font-semibold text-slate-400 tracking-wide mt-0.5 uppercase">
            RÉF: {{ selectedDossier.reference || 'N/A' }}
          </p>
        </div>
      </div>
      <!-- Bouton fermer X -->
      <button 
        @click="showDossierModal = false" 
        class="text-slate-400 hover:text-white transition-colors p-1"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Corps de la modale -->
    <div class="p-6 space-y-6">
      <!-- Grille des métadonnées (2 colonnes) -->
      <div class="grid grid-cols-2 gap-y-5 gap-x-6">
        <!-- Service Émetteur -->
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Service Émetteur</p>
          <p class="text-sm font-bold text-slate-800">
            {{ selectedDossier?.service?.nom || selectedDossier?.service_nom || selectedDossier?.boite?.casier?.service?.nom || 'N/A' }}
          </p>
        </div>

        <!-- Emplacement / Casier -->
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Emplacement / Casier</p>
          <p class="text-sm font-bold text-slate-800">
            {{ selectedDossier?.boite?.nom || selectedDossier?.emplacement || 'Boîte undefined' }}
          </p>
        </div>

        <!-- Date d'ouverture -->
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date d'ouverture</p>
          <p class="text-sm font-bold text-slate-800">
            {{ formatDate(selectedDossier.created_at || selectedDossier.date_ouverture) }}
          </p>
        </div>

        <!-- Statut d'indexation -->
        <div>
          <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Statut d'indexation</p>
          <div class="flex items-center gap-1.5 text-sm font-bold text-emerald-600">
            <svg class="w-4 h-4 text-cyan-500 fill-current" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>Numérisé & Archivé</span>
          </div>
        </div>
      </div>

      <!-- Description / Section Document Numérisé -->
      <div class="space-y-1">
        <h4 class="text-sm font-bold text-slate-700">Document Numérisé</h4>
        <p class="text-xs text-slate-500 leading-relaxed">
          Ce document est disponible au format PDF sécurisé avec signature électronique municipale.
        </p>
      </div>

      <!-- Actions (Télécharger & Fermer) -->
      <div class="pt-2 flex items-center justify-between">
        <!-- Bouton Télécharger / Exporter en PDF -->
        <button 
              type="button"
              @click="exportPDF"
              class="inline-flex items-center gap-2 px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-xl shadow-md transition-colors"
          >
  
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
            Télécharger la copie numérique
        </button>

        <button 
          @click="showDossierModal = false"
          class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 transition-colors"
        >
          Fermer
        </button>
      </div>
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
                    <option v-for="service in services" :key="service.id" :value="service.id">
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

<!-- Fiche d'Archive officielle (Génération PDF) -->
<div style="display: none;">
  <div 
    id="pdf-content" 
    class="p-8 bg-white font-sans text-slate-800" 
    style="width: 190mm; box-sizing: border-box; margin: 0 auto;"
  >
    <!-- En-tête Officiel avec Logo -->
    <div class="border-b-2 border-slate-900 pb-4 mb-6 flex justify-between items-center gap-4">
      <div class="flex items-center gap-4">
        <!-- Logo de la Commune (Assurez-vous d'avoir l'image dans /public/images/logo.png) -->
        <img src="/images/logo.png" alt="Logo Commune" class="w-16 h-16 object-contain shrink-0" />
        <div>
          <h1 class="text-lg font-bold uppercase tracking-wide text-slate-900">Commune Urbaine de Mahajanga</h1>
          <p class="text-xs text-slate-500 font-semibold uppercase">Service de Gestion des Archives Numériques</p>
        </div>
      </div>
      
      <!-- Zone Référence -->
      <div class="text-right shrink-0">
  <span class="inline-block px-3 py-1 bg-slate-100 border border-slate-300 text-xs font-mono font-bold rounded">
    RÉF : {{ 
      selectedDossier?.reference || 
      selectedDossier?.ref || 
      selectedDossier?.num_ref || 
      selectedDossier?.numero_reference || 
      selectedDossier?.code || 
      (selectedDossier?.id ? `REF-2026-${selectedDossier.id}` : 'N/A') 
    }}
  </span>
</div>
    </div>

    <!-- Titre du Document -->
    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-6">
      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Intitulé du Dossier</p>
      <h2 class="text-base font-bold text-slate-900">
        {{ selectedDossier?.titre || selectedDossier?.intitule || selectedDossier?.nom || 'N/A' }}
      </h2>
    </div>

    <!-- Tableau des Métadonnées -->
    <table class="w-full border-collapse mb-6 text-xs">
      <tbody>
        <!-- Service Émetteur -->
        <tr class="border-b border-slate-200">
          <td class="py-2.5 px-2 font-bold text-slate-500 w-1/3 bg-slate-50/50">Service Émetteur</td>
          <td class="py-2.5 px-2 font-semibold text-slate-800">
            {{ 
              selectedDossier?.boite?.casier?.service?.nom || 
              selectedDossier?.service?.nom || 
              selectedDossier?.boite?.service?.nom || 
              selectedDossier?.service_nom || 
              'N/A' 
            }}
          </td>
        </tr>

        <!-- Emplacement / Casier -->
        <tr class="border-b border-slate-200">
          <td class="py-2.5 px-2 font-bold text-slate-500 bg-slate-50/50">Emplacement / Casier</td>
          <td class="py-2.5 px-2 font-semibold text-slate-800">
            {{ 
              [
                selectedDossier?.boite?.nom || selectedDossier?.boite?.numero_boite,
                selectedDossier?.boite?.casier?.nom || selectedDossier?.casier?.nom || selectedDossier?.casier
              ].filter(Boolean).join(' - ') || 'N/A' 
            }}
          </td>
        </tr>

        <!-- Date d'Ouverture / Création -->
        <tr class="border-b border-slate-200">
          <td class="py-2.5 px-2 font-bold text-slate-500 bg-slate-50/50">Date d'Ouverture / Création</td>
          <td class="py-2.5 px-2 font-semibold text-slate-800">
            {{ 
              selectedDossier?.created_at 
                ? new Date(selectedDossier.created_at).toLocaleDateString('fr-FR') 
                : (selectedDossier?.date_ouverture || 'N/A') 
            }}
          </td>
        </tr>

        <!-- Statut de Numérisation -->
        <tr class="border-b border-slate-200">
          <td class="py-2.5 px-2 font-bold text-slate-500 bg-slate-50/50">Statut de Numérisation</td>
          <td class="py-2.5 px-2 font-semibold text-emerald-700">✓ Numérisé et archivé dans le système</td>
        </tr>
      </tbody>
    </table>

    <!-- Description -->
    <div class="mb-6">
      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Description & Contenu</p>
      <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 leading-relaxed min-h-[60px]">
        {{ selectedDossier?.description || 'Archive regroupant les demandes officielles de congés payés, autorisations spéciales d\'absence, arrêtés maladie et justificatifs de présence déposés par les agents municipaux.' }}
      </div>
    </div>

    <!-- Zone de validation / Signature -->
    <div class="mt-8 pt-4 border-t border-slate-200 grid grid-cols-2 gap-8 text-xs">
      <div>
        <p class="font-bold text-slate-500">Agent responsable :</p>
        <p class="mt-10 font-semibold text-slate-800">Signature & Cachet</p>
      </div>
      <div class="text-right">
        <p class="font-bold text-slate-500">Fait à Mahajanga, le :</p>
        <p class="mt-1 text-slate-700">{{ new Date().toLocaleDateString('fr-FR') }}</p>
      </div>
    </div>

  </div>
</div>

</template>