<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SiteAnnouncement } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    announcements: SiteAnnouncement[];
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Annonces',
        href: route('admin.announcements.index'),
    },
]);

const form = useForm({
    title: '',
    content: '',
});

const submit = () => {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const remove = (id: number) => {
    if (confirm('Supprimer cette annonce ?')) {
        router.delete(route('admin.announcements.destroy', id), {
            preserveScroll: true,
        });
    }
};

const latestAnnouncement = computed(() => props.announcements.at(0) ?? null);

const formatDate = (value: string | null | undefined) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : null;
</script>

<template>
    <Head title="Annonces du site" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div v-if="props.flash?.success" class="rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-sm text-green-700">
                {{ props.flash.success }}
            </div>
            <div v-if="props.flash?.error" class="rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-700">
                {{ props.flash.error }}
            </div>

            <section class="grid gap-6 lg:grid-cols-3">
                <form
                    class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 lg:col-span-2"
                    @submit.prevent="submit"
                >
                    <header class="space-y-1">
                        <h1 class="text-lg font-semibold">Publier une annonce</h1>
                        <p class="text-sm text-gray-600 dark:text-neutral-400">
                            Partage des informations importantes avec l’ensemble des membres depuis cet espace dédié.
                        </p>
                    </header>

                    <div class="space-y-1">
                        <label for="announcement-title" class="text-sm font-medium text-gray-700 dark:text-neutral-200">Titre</label>
                        <input
                            id="announcement-title"
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-50"
                            :disabled="form.processing"
                            required
                        />
                        <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <div class="space-y-1">
                        <label for="announcement-content" class="text-sm font-medium text-gray-700 dark:text-neutral-200">Message</label>
                        <textarea
                            id="announcement-content"
                            v-model="form.content"
                            rows="6"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-50"
                            :disabled="form.processing"
                            required
                        />
                        <p v-if="form.errors.content" class="text-xs text-red-500">{{ form.errors.content }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/50 disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="form.processing"
                        >
                            Publier
                        </button>
                    </div>
                </form>

                <aside class="space-y-4 rounded-xl border border-blue-200 bg-blue-50 p-6 text-blue-900 shadow-sm dark:border-blue-800/60 dark:bg-blue-950/60 dark:text-blue-100">
                    <header class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-300">
                            Dernière annonce
                        </p>
                        <h2 class="text-lg font-semibold">
                            {{ latestAnnouncement?.title ?? 'Aucune annonce publiée pour le moment' }}
                        </h2>
                    </header>
                    <p v-if="latestAnnouncement?.author" class="text-xs text-blue-700/80 dark:text-blue-200/80">
                        Posté par
                        <span class="font-semibold">{{ latestAnnouncement.author.name ?? latestAnnouncement.author.username }}</span>
                    </p>
                    <p v-if="formatDate(latestAnnouncement?.published_at)" class="text-xs text-blue-700/80 dark:text-blue-200/80">
                        {{ formatDate(latestAnnouncement?.published_at) }}
                    </p>
                    <p v-if="latestAnnouncement" class="whitespace-pre-line text-sm leading-relaxed text-blue-800 dark:text-blue-100/90">
                        {{ latestAnnouncement.content }}
                    </p>
                    <p v-else class="text-sm text-blue-800/70 dark:text-blue-100/70">
                        Publie ta première annonce pour informer la communauté.
                    </p>
                </aside>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <header class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                    <h2 class="text-lg font-semibold">Historique des annonces</h2>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">
                        Retrouve ici les messages déjà diffusés. Supprime ceux qui ne sont plus pertinents.
                    </p>
                </header>
                <div class="divide-y divide-gray-200 dark:divide-neutral-800">
                    <article
                        v-for="announcement in announcements"
                        :key="announcement.id"
                        class="flex flex-col gap-4 px-6 py-5 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="space-y-2">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-neutral-100">{{ announcement.title }}</h3>
                            <p class="whitespace-pre-line text-sm text-gray-700 dark:text-neutral-200">
                                {{ announcement.content }}
                            </p>
                            <div class="text-xs text-gray-500 dark:text-neutral-400">
                                <p v-if="announcement.author">
                                    Publié par {{ announcement.author.name ?? announcement.author.username }}
                                </p>
                                <p v-if="formatDate(announcement.published_at)">
                                    {{ formatDate(announcement.published_at) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="rounded border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30"
                                @click="remove(announcement.id)"
                            >
                                Supprimer
                            </button>
                        </div>
                    </article>
                    <p v-if="announcements.length === 0" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-neutral-400">
                        Aucune annonce n’a encore été publiée.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
