<template>
  <aside 
    :class="[
      'fixed top-0 left-0 z-40 h-screen bg-[#1b253b] text-slate-300 transition-all duration-300 flex flex-col justify-between border-r border-slate-800',
      isCollapsed ? 'w-20' : 'w-72'
    ]"
  >
    <!-- En-tête : Logo & Titre -->
    <div>
      <div class="flex items-center gap-3 p-5 border-b border-slate-800/80">
        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white shrink-0 shadow-md p-1">
  <img 
    src="/images/logo.png" 
    alt="Logo Commune Urbaine de Mahajanga" 
    class="w-full h-full object-contain"
  />
</div>
        <div v-if="!isCollapsed" class="overflow-hidden whitespace-nowrap">
          <h1 class="font-bold text-white text-base leading-tight">Archivage Numérique</h1>
          <span class="text-[10px] font-semibold tracking-wider text-slate-400 uppercase">ADMIN</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="p-3 space-y-1.5 mt-2">
        <a 
          href="#" 
          :class="[
            'flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all duration-200',
            activeTab === 'dashboard' 
              ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' 
              : 'hover:bg-slate-800/60 hover:text-white'
          ]"
          @click.prevent="setActiveTab('dashboard')"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
          </svg>
          <span v-if="!isCollapsed" class="truncate">Tableau de Bord Global</span>
        </a>
      
        <a 
          href="#" 
          :class="[
            'flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all duration-200',
            activeTab === 'users' 
              ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' 
              : 'hover:bg-slate-800/60 hover:text-white'
          ]"
          @click.prevent="setActiveTab('users')"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span v-if="!isCollapsed" class="truncate">Utilisateurs Enregistrés</span>
        </a>

        <a 
          href="#" 
          :class="[
            'flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-medium transition-all duration-200',
            activeTab === 'logs' 
              ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' 
              : 'hover:bg-slate-800/60 hover:text-white'
          ]"
          @click.prevent="setActiveTab('logs')"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span v-if="!isCollapsed" class="truncate">Historique des Activités</span>
        </a>
      </nav>
    </div>

    <!-- Pied de page -->
    <div class="p-3 border-t border-slate-800/80 flex items-center justify-between">
      <div class="flex items-center gap-3 overflow-hidden">
        <div class="w-9 h-9 rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30 font-bold flex items-center justify-center shrink-0">
          A
        </div>
        <div v-if="!isCollapsed" class="truncate">
          <p class="text-xs font-semibold text-white leading-tight">Admin</p>
          <p class="text-[10px] text-slate-400 truncate">admin@maharanga.mg</p>
        </div>
      </div>

      <button 
        @click="toggleCollapse"
        class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
      >
        <svg 
          :class="['w-5 h-5 transition-transform duration-300', isCollapsed ? 'rotate-180' : '']" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
        </svg>
      </button>
    </div>
  </aside>
</template>

<script setup>
const props = defineProps({
  isCollapsed: Boolean,
  activeTab: String
})

const emit = defineEmits(['update:isCollapsed', 'update:activeTab'])

const toggleCollapse = () => {
  emit('update:isCollapsed', !props.isCollapsed)
}

const setActiveTab = (tab) => {
  emit('update:activeTab', tab)
}
</script>