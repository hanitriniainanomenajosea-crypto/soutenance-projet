<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';

defineProps({
    boites: Array
});
 
const emit = defineEmits(['close']);
const form = useForm({
    numero_reference: '', 
    titre: '',            
    description: '',
    boite_id: '',
    images: [],
});

// 2. Gestion des erreurs
const imageError = ref('');
const imagePreviews = ref([]);

// 3. Soumission du formulaire
const submit = () => {
    form.post(route('agent.dossiers.store'),{
        onSuccess:() =>  {
            form.reset();
            emit('close');
        }
    });
};

// 4. Fonction de contrôle et de limite des 3 images
const handleImageUpload = (e) => {
    const files = Array.from(e.target.files);

    if (files.length > 3) {
        imageError.value = 'Vous ne pouvez choisir que 3 images maximum.';
        e.target.value = '';
        form.images = [];
        imagePreviews.value = [];
        return;
    }

    imageError.value = '';
    form.images = files;
    imagePreviews.value = files.map(file => URL.createObjectURL(file));
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 relative border border-slate-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-slate-800">Nouveau Dossier d'Archive</h2>
                <Link :href="route('agent.boites.index')" class="text-sm text-slate-500 hover:text-slate-700">
                    Annuler
                </Link>

            </div>

            <form @submit.prevent="submit" enctype="multipart/form-data">
                <div v-if="Object.keys(form.errors).length > 0" class="p-3 bg-red-100 text-red-700 rounded-lg text-sm">

                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Intitulé / Nom du dossier</label>
                    <input v-model="form.titre" type="text" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Numero du dossier</label>
                    <input v-model="form.numero_reference" type="text" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date d'ouverture</label>
                    <input v-model="form.date_ouverture" type="date" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ranger dans la boîte</label>
                    <select v-model="form.boite_id" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                        <option value="" disabled>Sélectionnez une boîte</option>
                        <option v-for="b in boites" :key="b.id" :value="b.id">
                            {{ b.casier ? b.casier.nom + ' — ' + (b.nom || b.code) : (b.nom || b.code) }}
                        </option>
                    </select>
                
                </div>
                
                <!-- Champ Téléchargement d'images (Max 3) -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Documents / Images jointes <span class="text-slate-400 font-normal">(Max 3 images)</span>
                </label>
            <input 
                type="file" 
                multiple 
                accept="image/*" 
                @change="handleImageUpload" 
                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 border border-slate-200 rounded-xl cursor-pointer p-1"
            />
    
            <!-- Alerte si plus de 3 images -->
            <p v-if="imageError" class="text-red-500 text-[11px] mt-1 font-medium">
                {{ imageError }}
            </p>

            <!-- Aperçu des fichiers sélectionnés -->
                <div v-if="form.images.length > 0" class="flex gap-2 mt-2">
                    <div v-for="(img, idx) in imagePreviews" :key="idx" class="w-12 h-12 rounded-lg border border-slate-200 overflow-hidden relative">
                    <img :src="img" class="w-full h-full object-cover" />
                </div>
            </div>
        </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description (Optionnel)</label>
                    <textarea v-model="form.description" rows="3" class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                </div>


                <div class="pt-4">
                    <button type="submit" :disabled="form.processing" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-medium text-sm hover:bg-indigo-700 transition-colors">
                        Enregistrer le dossier
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>