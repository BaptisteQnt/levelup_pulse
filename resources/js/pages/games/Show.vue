<script setup lang="ts">
import CompatibilityScanner from '@/components/games/CompatibilityScanner.vue';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { gameCoverUrl } from '@/lib/game-images';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type ArticleSummary = {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    is_premium: boolean;
    published_at: string | null;
    reactions_count: number;
    author: {
        name: string | null;
        username: string;
    };
};

const props = defineProps<{
    game: {
        id: number;
        title: string;
        slug: string;
        cover_url: string | null;
        summary: string | null;
        storyline: string | null;
        description: string | null;
        articles: ArticleSummary[];
        ratings: {
            enabled: boolean;
            average: number | null;
            count: number;
            user: number | null;
        };
    };
    canCreateArticle: boolean;
    flash?: string | null;
}>();

const page = usePage();
const auth = page.props.auth;

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Accueil', href: '/' },
    { title: 'Jeux', href: '/games' },
    { title: props.game.title, href: page.url },
]);

const displayText = computed(() => {
    const parts = [props.game.storyline, props.game.summary, props.game.description].filter((value): value is string =>
        Boolean(value && value.trim()),
    );

    return parts.length ? parts.join('\n\n') : null;
});

const ratingForm = useForm({
    rating: props.game.ratings.enabled ? (props.game.ratings.user ?? null) : null,
});

const userRating = ref<number | null>(props.game.ratings.enabled ? (props.game.ratings.user ?? null) : null);

watch(
    () => props.game.ratings,
    (value) => {
        if (!value.enabled) {
            ratingForm.rating = null;
            userRating.value = null;
            return;
        }

        ratingForm.rating = value.user ?? null;
        userRating.value = value.user ?? null;
    },
    { deep: true },
);

const stars = computed(() => Array.from({ length: 10 }, (_, index) => index + 1));

const ratingSummary = computed(() => {
    if (!props.game.ratings.enabled) {
        return 'Les notes ne sont pas disponibles pour le moment.';
    }

    const { average, count } = props.game.ratings;

    if (!count || average === null) {
        return 'Aucune note pour le moment.';
    }

    return `Moyenne : ${average}/10 (${count} ${count > 1 ? 'notes' : 'note'})`;
});

const setRating = (value: number) => {
    if (!props.game.ratings.enabled || !auth.user) {
        return;
    }

    ratingForm.rating = value;
    userRating.value = value;

    ratingForm.post(route('games.rating.store', props.game.id), {
        preserveScroll: true,
        onError: () => {
            userRating.value = props.game.ratings.user ?? null;
        },
    });
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
          }).format(new Date(value))
        : null;
</script>

<template>
    <Head :title="game.title" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl px-4 py-10">
            <div v-if="flash" class="mb-6 rounded border border-green-300 bg-green-100 px-4 py-2 text-green-800">
                {{ flash }}
            </div>

            <section class="grid gap-8 md:grid-cols-[220px_1fr]">
                <img
                    v-if="gameCoverUrl(game.cover_url)"
                    :src="gameCoverUrl(game.cover_url) ?? undefined"
                    alt="Couverture du jeu"
                    class="w-full rounded-lg shadow-md"
                />
                <div>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-primary text-xs font-semibold uppercase">Fiche jeu</p>
                            <h1 class="text-3xl font-bold text-[#001C55] dark:text-[#A6E1FA]">{{ game.title }}</h1>
                        </div>
                        <Link
                            v-if="canCreateArticle"
                            :href="route('articles.create', game.id)"
                            class="bg-primary hover:bg-primary/90 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow transition"
                        >
                            Rediger un article
                        </Link>
                    </div>

                    <p class="mt-6 text-base leading-relaxed whitespace-pre-line text-gray-700 dark:text-[#A6E1FA]">
                        {{ displayText ?? 'Aucune description disponible.' }}
                    </p>
                </div>
            </section>

            <CompatibilityScanner :game-id="game.id" :game-slug="game.slug" :game-title="game.title" />

            <section class="mt-10 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-[#00072d]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold dark:text-[#A6E1FA]">Note des joueurs</h2>
                        <p class="text-sm text-gray-600 dark:text-[#A6E1FA]">{{ ratingSummary }}</p>
                    </div>
                    <div class="flex items-center gap-1" role="group" aria-label="Noter ce jeu sur dix">
                        <button
                            v-for="star in stars"
                            :key="star"
                            type="button"
                            :disabled="ratingForm.processing || !auth.user || !game.ratings.enabled"
                            @click="setRating(star)"
                            class="text-2xl transition-colors focus:ring-2 focus:ring-blue-500 focus:outline-none"
                            :class="[
                                userRating !== null && star <= userRating ? 'text-yellow-400' : 'text-gray-300',
                                auth.user && game.ratings.enabled ? 'hover:text-yellow-500' : 'cursor-not-allowed opacity-70',
                            ]"
                        >
                            <span aria-hidden="true">*</span>
                            <span class="sr-only">Attribuer la note {{ star }}/10</span>
                        </button>
                    </div>
                </div>
                <p v-if="auth.user && game.ratings.enabled" class="mt-2 text-sm text-gray-600 dark:text-[#A6E1FA]">
                    <span v-if="userRating !== null">Ta note : {{ userRating }}/10</span>
                    <span v-else>Clique sur une etoile pour noter ce jeu.</span>
                </p>
                <p v-else class="mt-2 text-sm text-gray-500">Connecte-toi pour attribuer une note.</p>
                <p v-if="ratingForm.errors.rating" class="mt-2 text-sm text-red-500">
                    {{ ratingForm.errors.rating }}
                </p>
            </section>

            <section class="mt-10">
                <header class="mb-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-primary text-xs font-semibold uppercase">Articles</p>
                        <h2 class="text-2xl font-bold text-[#001C55] dark:text-[#A6E1FA]">Publications autour du jeu</h2>
                    </div>
                </header>

                <div v-if="game.articles.length" class="grid gap-4 md:grid-cols-2">
                    <article
                        v-for="article in game.articles"
                        :key="article.id"
                        class="hover:border-primary/60 hover:bg-primary/5 rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <div class="mb-3 flex items-center justify-between gap-3 text-xs text-gray-600 dark:text-neutral-400">
                            <span>Par {{ article.author.name ?? article.author.username }}</span>
                            <span v-if="article.is_premium" class="rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-700"> Premium </span>
                        </div>
                        <h3 class="text-lg font-semibold">
                            <Link :href="route('articles.show', article.slug)" class="hover:text-primary">
                                {{ article.title }}
                            </Link>
                        </h3>
                        <p class="mt-2 line-clamp-3 text-sm text-gray-600 dark:text-neutral-400">
                            {{ article.excerpt }}
                        </p>
                        <div class="mt-4 flex items-center justify-between text-xs text-gray-500">
                            <span>{{ formatDate(article.published_at) ?? 'Non date' }}</span>
                            <span>{{ article.reactions_count }} reactions</span>
                        </div>
                    </article>
                </div>
                <p
                    v-else
                    class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-600 dark:border-neutral-700 dark:text-neutral-400"
                >
                    Aucun article publie pour ce jeu.
                </p>
            </section>
        </div>
    </AppHeaderLayout>
</template>
