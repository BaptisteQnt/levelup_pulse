<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface AuditUser {
    id: number;
    name: string | null;
    username: string;
    email: string;
}

interface AuditLogItem {
    id: number;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    metadata: Record<string, unknown>;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string | null;
    actor: AuditUser | null;
    target_user: AuditUser | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    logs: {
        data: AuditLogItem[];
        links: PaginationLink[];
        meta: {
            current_page: number;
            last_page: number;
        };
    };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Securite', href: route('security.audit-logs.index') },
]);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const formatMetadata = (metadata: Record<string, unknown>) => {
    if (!metadata || Object.keys(metadata).length === 0) {
        return '-';
    }

    return JSON.stringify(metadata, null, 2);
};
</script>

<template>
    <Head title="Journal securite" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <header class="mb-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase text-primary">Securite</p>
                            <h1 class="text-2xl font-semibold">Journal des actions internes</h1>
                            <p class="mt-1 text-sm text-gray-600 dark:text-neutral-400">
                                Trace les actions des comptes internes : articles, annonces, roles et demandes RGPD.
                            </p>
                        </div>
                        <a
                            href="/telescope"
                            class="inline-flex items-center justify-center rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary transition hover:bg-primary/10"
                        >
                            Ouvrir Telescope
                        </a>
                    </div>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-neutral-800">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-neutral-950 dark:text-neutral-400">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Action</th>
                                <th class="px-4 py-3">Acteur</th>
                                <th class="px-4 py-3">Cible</th>
                                <th class="px-4 py-3">Objet</th>
                                <th class="px-4 py-3">IP</th>
                                <th class="px-4 py-3">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-800">
                            <tr v-for="log in logs.data" :key="log.id" class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-neutral-400">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-4 py-3 font-semibold">{{ log.action }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="log.actor">
                                        {{ log.actor.name ?? log.actor.username }}
                                        <span class="block text-xs text-gray-500">@{{ log.actor.username }}</span>
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="log.target_user">
                                        {{ log.target_user.name ?? log.target_user.username }}
                                        <span class="block text-xs text-gray-500">@{{ log.target_user.username }}</span>
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-neutral-400">
                                    <span v-if="log.auditable_type">{{ log.auditable_type }} #{{ log.auditable_id }}</span>
                                    <span v-else>-</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-neutral-400">{{ log.ip_address ?? '-' }}</td>
                                <td class="min-w-72 px-4 py-3">
                                    <pre class="max-h-48 overflow-auto rounded bg-gray-50 p-3 text-xs text-gray-700 dark:bg-neutral-950 dark:text-neutral-300">{{ formatMetadata(log.metadata) }}</pre>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                    Aucun evenement interne journalise.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav v-if="logs.meta.last_page > 1" class="mt-6 flex justify-center">
                    <ul class="flex items-center gap-2">
                        <li v-for="link in logs.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="rounded border px-3 py-1 text-sm"
                                :class="link.active ? 'border-primary bg-primary text-white' : 'border-gray-200 hover:border-primary'"
                                v-html="link.label"
                            />
                            <span v-else class="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </section>
        </div>
    </AppLayout>
</template>
