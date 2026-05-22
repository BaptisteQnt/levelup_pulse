<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type DataRequest = {
    id: number;
    request_type: 'account_deletion' | 'data_deletion';
    details: string | null;
    status: 'pending' | 'in_progress' | 'resolved';
    admin_notes: string | null;
    created_at: string | null;
    resolved_at: string | null;
    user: {
        id: number;
        name: string | null;
        username: string;
        email: string;
    };
};

type FlashMessage = {
    success?: string | null;
    error?: string | null;
};

const props = defineProps<{
    requests: DataRequest[];
    flash?: FlashMessage;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Demandes RGPD',
        href: route('admin.privacy.requests.index'),
    },
]);

const statusOptions: { value: DataRequest['status']; label: string }[] = [
    { value: 'pending', label: 'En attente' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'resolved', label: 'Résolue' },
];

const typeLabels: Record<DataRequest['request_type'], string> = {
    account_deletion: 'Suppression du compte',
    data_deletion: 'Suppression des données personnelles',
};

const initialForms = Object.fromEntries(
    props.requests.map((request) => [
        request.id,
        {
            status: request.status,
            admin_notes: request.admin_notes ?? '',
        },
    ]),
) as Record<number, { status: DataRequest['status']; admin_notes: string }>;

const forms = reactive(initialForms);

const flash = computed(() => props.flash);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '—';

const updateRequest = (id: number) => {
    const payload = forms[id];

    router.patch(route('admin.privacy.requests.update', id), payload, {
        preserveScroll: true,
    });
};

const confirmAccountDeletion = (request: DataRequest) => {
    if (
        !confirm(
            "Êtes-vous sûr de vouloir supprimer définitivement ce compte utilisateur ? Cette action est irréversible et entraînera la suppression de toutes les données associées.",
        )
    ) {
        return;
    }

    router.post(route('admin.privacy.requests.destroy-account', request.id), {}, {
        preserveScroll: true,
    });
};

const anonymizePersonalData = (request: DataRequest) => {
    if (
        !confirm(
            "Confirmez-vous l'anonymisation des données personnelles pour cet utilisateur ? Les informations sensibles seront effacées sans supprimer le compte.",
        )
    ) {
        return;
    }

    router.post(route('admin.privacy.requests.erase-data', request.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Demandes RGPD" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div v-if="flash?.success" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/40 dark:text-red-200">
                {{ flash.error }}
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <header class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <h1 class="text-lg font-semibold">Demandes de suppression de données</h1>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">
                        Consultez et traitez les demandes envoyées par les membres pour exercer leurs droits RGPD.
                    </p>
                </header>

                <div v-if="props.requests.length === 0" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-neutral-400">
                    Aucune demande à afficher pour le moment.
                </div>

                <ul v-else class="divide-y divide-gray-200 dark:divide-neutral-800">
                    <li
                        v-for="request in props.requests"
                        :key="request.id"
                        class="flex flex-col gap-6 px-6 py-5 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="space-y-4 lg:max-w-2xl">
                            <div class="space-y-1">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ typeLabels[request.request_type] }}
                                </h2>
                                <p class="text-sm text-gray-600 dark:text-neutral-300">
                                    <span class="font-medium text-gray-900 dark:text-neutral-100">{{ request.user.name ?? request.user.username }}</span>
                                    • @{{ request.user.username }} • {{ request.user.email }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">
                                    Créée le {{ formatDate(request.created_at) }}
                                    <span v-if="request.resolved_at"> • Résolue le {{ formatDate(request.resolved_at) }}</span>
                                </p>
                            </div>
                            <p v-if="request.details" class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-200">
                                {{ request.details }}
                            </p>
                        </div>

                        <form class="flex-1 space-y-4" @submit.prevent="updateRequest(request.id)">
                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-neutral-300">
                                    Statut de la demande
                                </label>
                                <select
                                    v-model="forms[request.id].status"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
                                >
                                    <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-neutral-300">
                                    Notes internes
                                </label>
                                <textarea
                                    v-model="forms[request.id].admin_notes"
                                    rows="4"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
                                    placeholder="Ajoutez des précisions sur le traitement de la demande."
                                ></textarea>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    Enregistrer les modifications
                                </button>
                                <button
                                    v-if="request.request_type === 'account_deletion'"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                                    @click="confirmAccountDeletion(request)"
                                >
                                    Supprimer le compte
                                </button>
                                <button
                                    v-if="request.request_type === 'data_deletion'"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600"
                                    @click="anonymizePersonalData(request)"
                                >
                                    Supprimer les données personnelles
                                </button>
                            </div>
                        </form>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
