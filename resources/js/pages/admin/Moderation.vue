<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

type ModeratedContent = {
    id: number;
    content: string;
    created_at: string | null;
    user: { id: number; username: string };
    game: { id: number; title: string; slug: string };
};

defineProps<{
    pendingComments: ModeratedContent[];
    pendingTips: ModeratedContent[];
    flash?: {
        success?: string | null;
        error?: string | null;
    };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
    {
        title: 'Modération',
        href: route('admin.moderation.index'),
    },
]);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '—';

const approveComment = (id: number) => {
    router.patch(route('admin.comments.approve', id), {}, {
        preserveScroll: true,
    });
};

const approveTip = (id: number) => {
    router.patch(route('admin.tips.approve', id), {}, {
        preserveScroll: true,
    });
};

const deleteComment = (id: number) => {
    if (confirm('Supprimer ce commentaire ?')) {
        router.delete(route('comments.destroy', id), {
            preserveScroll: true,
        });
    }
};

const deleteTip = (id: number) => {
    if (confirm('Supprimer cette astuce ?')) {
        router.delete(route('tips.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Espace de modération" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div v-if="flash?.success" class="rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-sm text-green-700">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-700">
                {{ flash.error }}
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <header class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                        <h1 class="text-lg font-semibold">Commentaires à valider ({{ pendingComments.length }})</h1>
                        <p class="text-sm text-gray-600 dark:text-neutral-400">
                            Vérifie et approuve les derniers retours des joueurs pour garantir un espace bienveillant.
                        </p>
                    </header>

                    <div class="divide-y divide-gray-200 dark:divide-neutral-800">
                        <article
                            v-for="comment in pendingComments"
                            :key="`comment-${comment.id}`"
                            class="flex flex-col gap-4 px-6 py-5 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">
                                    @{{ comment.user.username }} •
                                    <Link :href="route('games.show', { slug: comment.game.slug })" class="hover:underline">
                                        {{ comment.game.title }}
                                    </Link>
                                </p>
                                <p class="text-gray-700 dark:text-neutral-200">{{ comment.content }}</p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">{{ formatDate(comment.created_at) }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    class="rounded border border-emerald-200 bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 dark:border-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                    @click="approveComment(comment.id)"
                                >
                                    Valider
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30"
                                    @click="deleteComment(comment.id)"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </article>
                        <p
                            v-if="pendingComments.length === 0"
                            class="px-6 py-10 text-center text-sm text-gray-500 dark:text-neutral-400"
                        >
                            Aucun commentaire en attente pour le moment. Tout est déjà validé !
                        </p>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <header class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                        <h2 class="text-lg font-semibold">Astuces à valider ({{ pendingTips.length }})</h2>
                        <p class="text-sm text-gray-600 dark:text-neutral-400">
                            Approuve les astuces avant leur publication afin de préserver la qualité des échanges.
                        </p>
                    </header>

                    <div class="divide-y divide-gray-200 dark:divide-neutral-800">
                        <article
                            v-for="tip in pendingTips"
                            :key="`tip-${tip.id}`"
                            class="flex flex-col gap-4 px-6 py-5 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-purple-700 dark:text-purple-300">
                                    @{{ tip.user.username }} •
                                    <Link :href="route('games.show', { slug: tip.game.slug })" class="hover:underline">
                                        {{ tip.game.title }}
                                    </Link>
                                </p>
                                <p class="text-gray-700 dark:text-neutral-200">{{ tip.content }}</p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">{{ formatDate(tip.created_at) }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    class="rounded border border-emerald-200 bg-emerald-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-600 dark:border-emerald-700 dark:bg-emerald-600 dark:hover:bg-emerald-500"
                                    @click="approveTip(tip.id)"
                                >
                                    Valider
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/30"
                                    @click="deleteTip(tip.id)"
                                >
                                    Supprimer
                                </button>
                            </div>
                        </article>
                        <p v-if="pendingTips.length === 0" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-neutral-400">
                            Aucune astuce en attente de validation pour le moment.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
