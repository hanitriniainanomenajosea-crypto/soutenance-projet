<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex flex-col justify-center items-center p-4">
        <Head title="Connexion - Système d'Archivage" />

        <div class="w-full max-w-md">
            <!-- En-tête avec logo institutionnel -->
<div class="text-center mb-6">
    <div class="inline-flex items-center justify-center mb-3">
        <img 
            src="/images/logo.png" 
            alt="Logo Commune Urbaine de Mahajanga" 
            class="w-16 h-16 object-contain drop-shadow-md"
        />
    </div>
    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Gestion des Archives</h1>
    <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 mt-1">Commune Urbaine de Mahajanga</p>
</div>

            <!-- Formulaire de connexion -->
            <div class="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-xl">
                <div v-if="status" class="mb-4 text-sm font-medium text-emerald-600 bg-emerald-50 p-3 rounded-xl border border-emerald-200">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="email" value="Adresse Email" class="text-xs font-bold uppercase tracking-wider text-slate-700" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="agent@commune.mg"
                        />
                        <InputError class="mt-1.5" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Mot de passe" class="text-xs font-bold uppercase tracking-wider text-slate-700" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1.5 block w-full rounded-xl border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-1.5" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <span class="text-slate-600 font-medium">Se souvenir de moi</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-semibold text-blue-600 hover:text-blue-700 hover:underline transition-colors"
                        >
                            Mot de passe oublié ?
                        </Link>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2"
                        >
                            Se connecter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>