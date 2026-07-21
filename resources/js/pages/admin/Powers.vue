<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UserSummary {
    id: number;
    name: string | null;
    username: string;
    email: string;
    is_admin: boolean;
    is_editor: boolean;
    is_super_admin: boolean;
    is_security_officer: boolean;
    joined_at: string | null;
}

const props = defineProps<{
    users: UserSummary[];
    canManageSensitiveRoles: boolean;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: route('dashboard') },
    { title: 'Pouvoirs', href: route('admin.powers.index') },
]);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const updateRole = (user: UserSummary, role: 'admin' | 'editor' | 'super_admin' | 'security_officer') => {
    router.patch(
        route('admin.powers.update', user.id),
        {
            is_admin: role === 'admin' ? !user.is_admin : user.is_admin,
            is_editor: role === 'editor' ? !user.is_editor : user.is_editor,
            is_super_admin: role === 'super_admin' ? !user.is_super_admin : user.is_super_admin,
            is_security_officer: role === 'security_officer' ? !user.is_security_officer : user.is_security_officer,
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Gestion des pouvoirs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div v-if="flash?.success" class="rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-sm text-green-700">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-700">
                {{ flash.error }}
            </div>

            <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <header class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <h1 class="text-lg font-semibold">Gestion des pouvoirs</h1>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">
                        Attribue les roles internes. Les roles super admin et responsable securite sont reserves au super admin.
                    </p>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-neutral-800">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-neutral-950 dark:text-neutral-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Membre</th>
                                <th scope="col" class="px-6 py-3">Roles</th>
                                <th scope="col" class="px-6 py-3">Inscrit le</th>
                                <th scope="col" class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-800">
                            <tr v-for="user in users" :key="user.id" class="bg-white dark:bg-neutral-900">
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <Link :href="route('users.show', { username: user.username })" class="font-semibold hover:underline">
                                            {{ user.username }}
                                        </Link>
                                        <p class="text-xs text-gray-500 dark:text-neutral-400">{{ user.email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-if="user.is_super_admin"
                                            class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700 dark:bg-purple-500/20 dark:text-purple-300"
                                        >
                                            Super admin
                                        </span>
                                        <span
                                            v-if="user.is_security_officer"
                                            class="inline-flex items-center rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300"
                                        >
                                            Responsable securite
                                        </span>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="user.is_admin
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300'
                                                : 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-neutral-300'"
                                        >
                                            {{ user.is_admin ? 'Administrateur' : 'Non admin' }}
                                        </span>
                                        <span
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                            :class="user.is_editor
                                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300'
                                                : 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-neutral-300'"
                                        >
                                            {{ user.is_editor ? 'Redacteur' : 'Lecteur' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-neutral-400">
                                    {{ formatDate(user.joined_at) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <Button type="button" variant="outline" size="sm" @click="updateRole(user, 'admin')">
                                            {{ user.is_admin ? 'Retirer admin' : 'Donner admin' }}
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" @click="updateRole(user, 'editor')">
                                            {{ user.is_editor ? 'Retirer redacteur' : 'Donner redacteur' }}
                                        </Button>
                                        <Button
                                            v-if="props.canManageSensitiveRoles"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="updateRole(user, 'security_officer')"
                                        >
                                            {{ user.is_security_officer ? 'Retirer securite' : 'Donner securite' }}
                                        </Button>
                                        <Button
                                            v-if="props.canManageSensitiveRoles"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="updateRole(user, 'super_admin')"
                                        >
                                            {{ user.is_super_admin ? 'Retirer super admin' : 'Donner super admin' }}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-neutral-400">
                                    Aucun membre a afficher pour le moment.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
