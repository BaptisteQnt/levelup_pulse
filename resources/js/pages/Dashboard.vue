<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { fetchDashboardStats, type DashboardStats } from '@/lib/stats';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Megaphone, Newspaper, ShieldCheck, UserMinus } from 'lucide-vue-next';
import { computed, onMounted, ref, watch, type Component } from 'vue';

interface AdminAction {
    title: string;
    description: string;
    href: string;
    icon: Component;
}

interface RecentDashboardGame {
    id: number;
    title: string;
    slug: string;
    cover_url: string | null;
    searched_at: string | null;
}

interface LatestArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    is_premium: boolean;
    published_at: string | null;
    game: {
        title: string;
        slug: string;
    };
    author: {
        name: string | null;
        username: string;
    };
}

const page = usePage<SharedData & { recentGames?: RecentDashboardGame[]; latestArticles?: LatestArticle[]; stats?: DashboardStats }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Accueil', href: '/' }];
const isAdmin = computed(() => page.props.auth.user?.is_admin || page.props.auth.user?.is_super_admin || false);
const latestArticles = computed(() => page.props.latestArticles ?? []);
const recentGames = computed(() => page.props.recentGames ?? []);
const currentAnnouncement = computed(() => page.props.announcement ?? null);

const adminLinks = computed<AdminAction[]>(() => [
    {
        title: 'Pouvoirs',
        description: 'Gere les droits administrateur et redacteur.',
        href: route('admin.powers.index'),
        icon: ShieldCheck,
    },
    {
        title: 'Annonces',
        description: 'Publie les messages importants du media.',
        href: route('admin.announcements.index'),
        icon: Megaphone,
    },
    {
        title: 'Demandes RGPD',
        description: 'Traite les requetes de suppression de compte et de donnees personnelles.',
        href: route('admin.privacy.requests.index'),
        icon: UserMinus,
    },
]);

const sharedStats = computed(() => page.props.stats ?? null);
const stats = ref<DashboardStats | null>(sharedStats.value);
const isStatsLoading = ref(!stats.value);
const statsError = ref<string | null>(null);
const articleStats = computed(() => ({
    published_total: stats.value?.articles?.published_total ?? 0,
    premium_total: stats.value?.articles?.premium_total ?? 0,
}));

const loadStats = async () => {
    try {
        isStatsLoading.value = true;
        statsError.value = null;
        stats.value = await fetchDashboardStats();
    } catch (error) {
        statsError.value =
            error instanceof Error
                ? error.message
                : 'Une erreur est survenue lors du chargement des statistiques.';
    } finally {
        isStatsLoading.value = false;
    }
};

onMounted(() => {
    if (!stats.value) {
        void loadStats();
    }
});

watch(sharedStats, (value) => {
    if (value) {
        stats.value = value;
    }
});

const formatDate = (value: string | null | undefined) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : null;

const formatNumber = (value: number | null | undefined) =>
    value == null ? '-' : value.toLocaleString('fr-FR');

const formatAverage = (value: number | null | undefined) =>
    value == null
        ? '-'
        : value.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
</script>

<template>
    <Head title="Accueil" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-6">
            <section
                v-if="currentAnnouncement"
                class="space-y-3 rounded-lg border border-blue-200 bg-blue-50 p-6 text-blue-900 shadow-sm dark:border-blue-800/60 dark:bg-blue-950/60 dark:text-blue-100"
            >
                <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase text-blue-600 dark:text-blue-300">Information du site</p>
                        <h2 class="text-lg font-semibold">{{ currentAnnouncement.title }}</h2>
                    </div>
                    <div class="text-xs text-blue-700/80 dark:text-blue-200/80">
                        <p v-if="currentAnnouncement.author">
                            Poste par
                            <span class="font-semibold">
                                {{ currentAnnouncement.author.name ?? currentAnnouncement.author.username }}
                            </span>
                        </p>
                        <p v-if="formatDate(currentAnnouncement.published_at)">
                            {{ formatDate(currentAnnouncement.published_at) }}
                        </p>
                    </div>
                </header>
                <p class="whitespace-pre-line text-sm leading-relaxed text-blue-800 dark:text-blue-100/90">
                    {{ currentAnnouncement.content }}
                </p>
            </section>

            <section
                v-if="isAdmin"
                class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <header>
                    <h2 class="text-lg font-semibold">Menu administrateur</h2>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">
                        Accede aux outils de gestion du media.
                    </p>
                </header>

                <div class="grid gap-4 md:grid-cols-3">
                    <Link
                        v-for="link in adminLinks"
                        :key="link.title"
                        :href="link.href"
                        class="group flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-white p-5 transition hover:border-primary hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <div class="flex items-start gap-4">
                            <span class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white dark:bg-primary/20">
                                <component :is="link.icon" class="size-5" />
                            </span>
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold">{{ link.title }}</h3>
                                <p class="text-xs text-gray-600 dark:text-neutral-400">{{ link.description }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-primary transition group-hover:underline">Acceder</span>
                    </Link>
                </div>
            </section>

            <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <header class="mb-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase text-primary">A la une</p>
                            <h1 class="text-2xl font-semibold">Derniers articles publies</h1>
                        </div>
                        <Newspaper class="size-8 text-primary" />
                    </header>

                    <div v-if="latestArticles.length" class="grid gap-4 md:grid-cols-2">
                        <article
                            v-for="article in latestArticles"
                            :key="article.id"
                            class="rounded-lg border border-gray-200 p-4 transition hover:border-primary/60 hover:bg-primary/5 dark:border-neutral-800"
                        >
                            <div class="mb-3 flex items-center justify-between gap-3 text-xs text-gray-600 dark:text-neutral-400">
                                <Link :href="route('games.show', article.game.slug)" class="font-semibold text-primary hover:underline">
                                    {{ article.game.title }}
                                </Link>
                                <span v-if="article.is_premium" class="rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-700">
                                    Premium
                                </span>
                            </div>
                            <h2 class="text-lg font-semibold">
                                <Link :href="route('articles.show', article.slug)" class="hover:text-primary">
                                    {{ article.title }}
                                </Link>
                            </h2>
                            <p class="mt-2 line-clamp-3 text-sm text-gray-600 dark:text-neutral-400">
                                {{ article.excerpt }}
                            </p>
                            <p class="mt-4 text-xs text-gray-500 dark:text-neutral-500">
                                Par {{ article.author.name ?? article.author.username }}
                                <span v-if="formatDate(article.published_at)">- {{ formatDate(article.published_at) }}</span>
                            </p>
                        </article>
                    </div>
                    <p v-else class="text-sm text-gray-600 dark:text-neutral-400">
                        Aucun article publie pour le moment.
                    </p>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    <header class="space-y-1">
                        <p class="text-xs font-semibold uppercase text-primary">Bibliotheque</p>
                        <h2 class="text-lg font-semibold">Derniers jeux ajoutes</h2>
                    </header>

                    <ul v-if="recentGames.length" class="mt-4 space-y-3">
                        <li v-for="game in recentGames" :key="game.id">
                            <Link :href="route('games.show', game.slug)" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 transition hover:border-primary/60 hover:bg-primary/5 dark:border-neutral-800">
                                <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-md bg-gray-100 text-xs font-semibold uppercase text-gray-500 dark:bg-neutral-800">
                                    <img v-if="game.cover_url" :src="game.cover_url" :alt="`Jaquette de ${game.title}`" class="size-full object-cover" />
                                    <span v-else>{{ game.title.slice(0, 2) }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">{{ game.title }}</p>
                                    <p class="text-xs text-gray-500">{{ formatDate(game.searched_at) ?? '-' }}</p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-gray-600 dark:text-neutral-400">
                        Aucun jeu recent.
                    </p>
                </section>
            </div>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                <header class="mb-4">
                    <p class="text-xs font-semibold uppercase text-primary">Synthese</p>
                    <h2 class="text-lg font-semibold">Vue generale du media</h2>
                </header>

                <div v-if="isStatsLoading" class="text-sm text-gray-600 dark:text-neutral-400">
                    Chargement des statistiques...
                </div>
                <div v-else-if="statsError" class="text-sm text-red-600 dark:text-red-400">
                    {{ statsError }}
                </div>
                <dl v-else-if="stats" class="grid gap-4 md:grid-cols-5">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-neutral-800/50">
                        <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Jeux</dt>
                        <dd class="text-lg font-semibold">{{ formatNumber(stats.games.total) }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-neutral-800/50">
                        <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Articles publies</dt>
                        <dd class="text-lg font-semibold">{{ formatNumber(articleStats.published_total) }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-neutral-800/50">
                        <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Articles premium</dt>
                        <dd class="text-lg font-semibold">{{ formatNumber(articleStats.premium_total) }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-neutral-800/50">
                        <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Notes jeux</dt>
                        <dd class="text-lg font-semibold">{{ formatNumber(stats.ratings.total) }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-neutral-800/50">
                        <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Moyenne</dt>
                        <dd class="text-lg font-semibold">{{ formatAverage(stats.ratings.average) }} / 10</dd>
                    </div>
                </dl>
            </section>
        </div>
    </AppHeaderLayout>
</template>
