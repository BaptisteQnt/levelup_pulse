<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { gameCoverUrl } from '@/lib/game-images';
import { fetchDashboardStats, type DashboardStats } from '@/lib/stats';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, Crown, Gamepad2, Megaphone, Newspaper, ShieldCheck, Sparkles, Star, UserMinus, Users } from 'lucide-vue-next';
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
    image_url: string | null;
    is_premium: boolean;
    published_at: string | null;
    game: {
        title: string;
        slug: string;
        cover_url: string | null;
    };
    author: {
        name: string | null;
        username: string;
    };
}

interface DashboardPageProps extends SharedData {
    recentGames?: RecentDashboardGame[];
    latestArticles?: LatestArticle[];
    stats?: DashboardStats;
}

const page = usePage<DashboardPageProps>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Accueil', href: '/' }];
const isAdmin = computed(() => page.props.auth.user?.is_admin || page.props.auth.user?.is_super_admin || false);
const latestArticles = computed(() => page.props.latestArticles ?? []);
const featureArticle = computed(() => latestArticles.value[0] ?? null);
const secondaryArticles = computed(() => latestArticles.value.slice(1));
const recentGames = computed(() => page.props.recentGames ?? []);
const currentAnnouncement = computed(() => page.props.announcement ?? null);

const adminLinks = computed<AdminAction[]>(() => [
    {
        title: 'Pouvoirs',
        description: 'Gérer les droits administrateur et rédacteur.',
        href: route('admin.powers.index'),
        icon: ShieldCheck,
    },
    {
        title: 'Annonces',
        description: 'Publier les informations importantes du média.',
        href: route('admin.announcements.index'),
        icon: Megaphone,
    },
    {
        title: 'Demandes RGPD',
        description: 'Traiter les demandes liées aux données personnelles.',
        href: route('admin.privacy.requests.index'),
        icon: UserMinus,
    },
]);

const sharedStats = computed(() => page.props.stats ?? null);
const stats = ref<DashboardStats | null>(sharedStats.value);
const isStatsLoading = ref(!stats.value);
const statsError = ref<string | null>(null);

const loadStats = async () => {
    try {
        isStatsLoading.value = true;
        statsError.value = null;
        stats.value = await fetchDashboardStats();
    } catch (error) {
        statsError.value = error instanceof Error ? error.message : 'Une erreur est survenue lors du chargement des statistiques.';
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
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          }).format(new Date(value))
        : null;

const formatNumber = (value: number | null | undefined) => (value == null ? '—' : value.toLocaleString('fr-FR'));

const formatAverage = (value: number | null | undefined) =>
    value == null
        ? '—'
        : value.toLocaleString('fr-FR', {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
          });

const articleVisual = (article: LatestArticle) => article.image_url ?? gameCoverUrl(article.game.cover_url);

const statItems = computed(() => [
    {
        label: 'Note moyenne',
        value: `${formatAverage(stats.value?.ratings.average)} / 10`,
        icon: Star,
        featured: true,
        className: 'bg-gradient-to-br from-[#0E6BA8] to-[#001C55] text-white shadow-[0_18px_45px_-24px_rgba(0,28,85,0.8)]',
    },
    {
        label: 'Jeux',
        value: formatNumber(stats.value?.games.total),
        icon: Gamepad2,
        featured: false,
        className: 'bg-[#A6E1FA] text-[#001C55] dark:bg-[#0E6BA8] dark:text-white',
    },
    {
        label: 'Communauté',
        value: formatNumber(stats.value?.users.total),
        icon: Users,
        featured: false,
        className: 'bg-white text-[#001C55] dark:bg-[#001C55] dark:text-white',
    },
    {
        label: 'Articles',
        value: formatNumber(stats.value?.articles.published_total),
        icon: Newspaper,
        featured: false,
        className: 'bg-white text-[#001C55] dark:bg-[#001C55] dark:text-white',
    },
    {
        label: 'Premium',
        value: formatNumber(stats.value?.articles.premium_total),
        icon: Crown,
        featured: false,
        className: 'bg-amber-100 text-amber-950 dark:bg-amber-400/90 dark:text-amber-950',
    },
    {
        label: 'Jeux notés',
        value: formatNumber(stats.value?.games.rated_total),
        icon: Sparkles,
        featured: false,
        className: 'bg-[#001C55] text-white dark:bg-[#A6E1FA] dark:text-[#001C55]',
    },
    {
        label: 'Notes déposées',
        value: formatNumber(stats.value?.ratings.total),
        icon: BookOpen,
        featured: false,
        className: 'bg-[#0E6BA8] text-white dark:bg-white dark:text-[#001C55]',
    },
]);
</script>

<template>
    <Head title="Accueil" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="dashboard-surface relative isolate overflow-hidden px-4 py-6 sm:px-6 sm:py-8 lg:px-8 lg:py-10">
            <div aria-hidden="true" class="dashboard-glow dashboard-glow--top"></div>
            <div aria-hidden="true" class="dashboard-glow dashboard-glow--bottom"></div>

            <div class="relative z-10 space-y-8 lg:space-y-10">
                <section
                    v-if="currentAnnouncement"
                    class="flex flex-col gap-4 rounded-2xl border border-[#0E6BA8]/20 bg-white/80 p-4 shadow-sm backdrop-blur sm:flex-row sm:items-center sm:justify-between dark:border-[#A6E1FA]/20 dark:bg-[#001C55]/80"
                    aria-labelledby="announcement-title"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#A6E1FA]/70 text-[#001C55] dark:bg-[#0E6BA8] dark:text-white"
                        >
                            <Megaphone class="size-5" aria-hidden="true" />
                        </span>
                        <div>
                            <p class="text-xs font-bold tracking-[0.18em] text-[#001C55] uppercase dark:text-[#A6E1FA]">Flash info</p>
                            <h2 id="announcement-title" class="mt-0.5 text-base font-bold text-[#001C55] dark:text-white">
                                {{ currentAnnouncement.title }}
                            </h2>
                            <p class="mt-1 text-sm leading-relaxed whitespace-pre-line text-slate-600 dark:text-white/75">
                                {{ currentAnnouncement.content }}
                            </p>
                        </div>
                    </div>
                    <div class="shrink-0 text-xs text-slate-500 sm:text-right dark:text-white/60">
                        <p v-if="currentAnnouncement.author" class="font-semibold">
                            {{ currentAnnouncement.author.name ?? currentAnnouncement.author.username }}
                        </p>
                        <p v-if="formatDate(currentAnnouncement.published_at)">
                            {{ formatDate(currentAnnouncement.published_at) }}
                        </p>
                    </div>
                </section>

                <section
                    v-if="isAdmin"
                    class="rounded-2xl border border-[#0E6BA8]/20 bg-[#001C55] p-4 text-white shadow-lg shadow-[#001C55]/10 sm:p-5"
                    aria-labelledby="admin-tools-title"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="lg:w-56 lg:shrink-0">
                            <p class="text-xs font-bold tracking-[0.18em] text-[#A6E1FA] uppercase">Espace privé</p>
                            <h2 id="admin-tools-title" class="mt-1 text-lg font-bold !text-white">Outils d’administration</h2>
                        </div>
                        <div class="grid flex-1 gap-3 md:grid-cols-3">
                            <Link
                                v-for="link in adminLinks"
                                :key="link.title"
                                :href="link.href"
                                class="group flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 p-3 transition duration-200 hover:-translate-y-0.5 hover:border-[#A6E1FA]/60 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-[#A6E1FA] focus-visible:outline-none motion-reduce:transform-none"
                            >
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[#0E6BA8] text-white">
                                    <component :is="link.icon" class="size-4" aria-hidden="true" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold">{{ link.title }}</span>
                                    <span class="mt-0.5 block text-xs leading-snug text-white/65">{{ link.description }}</span>
                                </span>
                            </Link>
                        </div>
                    </div>
                </section>

                <header class="max-w-3xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-[#001C55]/20 bg-white/90 px-3 py-1.5 text-xs font-bold tracking-[0.2em] text-[#001C55] uppercase shadow-sm backdrop-blur dark:border-[#A6E1FA]/20 dark:bg-[#001C55]/70 dark:text-[#A6E1FA]"
                    >
                        <span class="size-2 rounded-full bg-[#0E6BA8] shadow-[0_0_0_4px_rgba(14,107,168,0.12)] dark:bg-[#A6E1FA]"></span>
                        Le fil LevelUp
                    </div>
                    <h1 class="mt-4 text-4xl font-black tracking-tight text-balance text-[#001C55] sm:text-5xl lg:text-6xl dark:text-white">
                        Le jeu vidéo,<br />
                        <span class="text-[#075985] dark:text-[#A6E1FA]">au rythme du moment.</span>
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg dark:text-white/70">
                        Les dernières histoires, les jeux fraîchement ajoutés et les chiffres qui font battre la communauté.
                    </p>
                </header>

                <div class="grid gap-5 lg:grid-cols-12 lg:items-stretch">
                    <section
                        v-if="featureArticle"
                        class="group relative min-h-[31rem] overflow-hidden rounded-[2rem] bg-[#001C55] shadow-[0_30px_70px_-35px_rgba(0,28,85,0.9)] lg:col-span-7 lg:min-h-[38rem]"
                        aria-labelledby="featured-article-title"
                    >
                        <img
                            v-if="articleVisual(featureArticle)"
                            :src="articleVisual(featureArticle) ?? undefined"
                            :alt="`Illustration de l’article ${featureArticle.title}`"
                            fetchpriority="high"
                            decoding="async"
                            class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
                        />
                        <div v-else aria-hidden="true" class="article-placeholder absolute inset-0"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-[#00072D] via-[#001C55]/65 to-[#001C55]/5"></div>
                        <div class="absolute inset-x-0 bottom-0 z-10 p-6 sm:p-8 lg:p-10">
                            <div class="mb-4 flex flex-wrap items-center gap-2 text-xs font-bold tracking-[0.14em] text-white/80 uppercase">
                                <Link
                                    :href="route('games.show', featureArticle.game.slug)"
                                    class="rounded-full bg-[#A6E1FA] px-3 py-1.5 text-[#001C55] transition hover:bg-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                >
                                    {{ featureArticle.game.title }}
                                </Link>
                                <span
                                    v-if="featureArticle.is_premium"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-300 px-3 py-1.5 text-amber-950"
                                >
                                    <Crown class="size-3.5" aria-hidden="true" /> Premium
                                </span>
                                <span v-if="formatDate(featureArticle.published_at)" class="px-1">
                                    {{ formatDate(featureArticle.published_at) }}
                                </span>
                            </div>
                            <p class="mb-2 text-xs font-bold tracking-[0.22em] text-[#A6E1FA] uppercase">À la une</p>
                            <h2 id="featured-article-title" class="max-w-3xl text-3xl leading-tight font-black !text-white sm:text-4xl lg:text-5xl">
                                {{ featureArticle.title }}
                            </h2>
                            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-white/75 sm:text-base">
                                {{ featureArticle.excerpt }}
                            </p>
                            <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                                <p class="text-sm text-white/65">
                                    Par
                                    <span class="font-bold text-white">
                                        {{ featureArticle.author.name ?? featureArticle.author.username }}
                                    </span>
                                </p>
                                <Link
                                    :href="route('articles.show', featureArticle.slug)"
                                    class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-bold text-[#001C55] shadow-lg transition duration-200 hover:-translate-y-0.5 hover:bg-[#A6E1FA] focus-visible:ring-2 focus-visible:ring-[#A6E1FA] focus-visible:ring-offset-2 focus-visible:ring-offset-[#001C55] focus-visible:outline-none motion-reduce:transform-none"
                                >
                                    Lire l’article
                                    <ArrowRight
                                        class="size-4 transition group-hover:translate-x-0.5 motion-reduce:transform-none"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </div>
                        </div>
                    </section>

                    <section
                        v-else
                        class="article-placeholder relative flex min-h-[31rem] items-end overflow-hidden rounded-[2rem] p-7 shadow-[0_30px_70px_-35px_rgba(0,28,85,0.9)] lg:col-span-7 lg:min-h-[38rem]"
                        aria-labelledby="empty-feature-title"
                    >
                        <div class="relative z-10 max-w-xl">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-white/15 text-white backdrop-blur">
                                <Newspaper class="size-6" aria-hidden="true" />
                            </span>
                            <p class="mt-5 text-xs font-bold tracking-[0.2em] text-[#A6E1FA] uppercase">À la une</p>
                            <h2 id="empty-feature-title" class="mt-2 text-3xl font-black !text-white sm:text-4xl">
                                La prochaine histoire se prépare.
                            </h2>
                            <p class="mt-3 text-white/70">Les nouveaux articles apparaîtront ici dès leur publication.</p>
                        </div>
                    </section>

                    <section
                        class="rounded-[2rem] border border-[#0E6BA8]/15 bg-white/75 p-5 shadow-[0_24px_55px_-38px_rgba(0,28,85,0.65)] backdrop-blur lg:col-span-5 lg:p-6 dark:border-[#A6E1FA]/15 dark:bg-[#00072D]/70"
                        aria-labelledby="stats-title"
                        aria-live="polite"
                    >
                        <div class="mb-5 flex items-end justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold tracking-[0.18em] text-[#001C55] uppercase dark:text-[#A6E1FA]">En direct</p>
                                <h2 id="stats-title" class="mt-1 text-2xl font-black text-[#001C55] dark:text-white">Le pouls du média</h2>
                            </div>
                            <span
                                class="flex size-10 items-center justify-center rounded-full bg-[#0E6BA8]/10 text-[#0E6BA8] dark:bg-[#A6E1FA]/10 dark:text-[#A6E1FA]"
                            >
                                <Sparkles class="size-5" aria-hidden="true" />
                            </span>
                        </div>

                        <div v-if="isStatsLoading" class="grid grid-cols-2 gap-3" aria-label="Chargement des statistiques">
                            <div
                                v-for="index in 7"
                                :key="index"
                                class="h-28 animate-pulse rounded-2xl bg-slate-200/80 dark:bg-white/10"
                                :class="index === 1 ? 'col-span-2 h-32' : ''"
                            ></div>
                        </div>
                        <div
                            v-else-if="statsError"
                            class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700 dark:border-red-400/30 dark:bg-red-950/30 dark:text-red-200"
                        >
                            <p class="font-bold">Statistiques indisponibles</p>
                            <p class="mt-1">{{ statsError }}</p>
                            <button
                                type="button"
                                class="mt-4 rounded-full border border-current px-4 py-2 font-bold hover:bg-red-100 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:outline-none dark:hover:bg-red-900/30"
                                @click="loadStats"
                            >
                                Réessayer
                            </button>
                        </div>
                        <dl v-else class="grid grid-cols-2 gap-3">
                            <div
                                v-for="item in statItems"
                                :key="item.label"
                                class="relative min-h-28 overflow-hidden rounded-2xl border border-[#0E6BA8]/10 p-4 transition duration-200 hover:-translate-y-0.5 motion-reduce:transform-none dark:border-white/10"
                                :class="[item.className, item.featured ? 'col-span-2 min-h-32' : '']"
                            >
                                <component :is="item.icon" class="absolute top-3 right-3 size-5 opacity-65" aria-hidden="true" />
                                <dt class="pr-7 text-xs font-bold tracking-[0.12em] uppercase opacity-70">{{ item.label }}</dt>
                                <dd class="mt-5 text-2xl font-black tracking-tight" :class="item.featured ? 'text-4xl' : ''">
                                    {{ item.value }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <section v-if="secondaryArticles.length" aria-labelledby="latest-articles-title">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold tracking-[0.18em] text-[#001C55] uppercase dark:text-[#A6E1FA]">À découvrir</p>
                            <h2 id="latest-articles-title" class="mt-1 text-3xl font-black text-[#001C55] sm:text-4xl dark:text-white">
                                Les dernières chroniques
                            </h2>
                        </div>
                        <p class="max-w-md text-sm text-slate-500 sm:text-right dark:text-white/60">
                            Analyses, actualités et regards de la rédaction sur les jeux du moment.
                        </p>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-12">
                        <article
                            v-for="(article, index) in secondaryArticles"
                            :key="article.id"
                            class="group overflow-hidden rounded-3xl border border-[#0E6BA8]/15 bg-white shadow-[0_18px_50px_-38px_rgba(0,28,85,0.7)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_24px_55px_-35px_rgba(0,28,85,0.65)] motion-reduce:transform-none dark:border-[#A6E1FA]/15 dark:bg-[#001C55]"
                            :class="index % 4 === 0 || index % 4 === 3 ? 'lg:col-span-7' : 'lg:col-span-5'"
                        >
                            <div class="grid h-full sm:grid-cols-[minmax(10rem,0.8fr)_1.2fr]">
                                <div class="relative min-h-52 overflow-hidden bg-[#001C55] sm:min-h-64">
                                    <img
                                        v-if="articleVisual(article)"
                                        :src="articleVisual(article) ?? undefined"
                                        :alt="`Illustration de l’article ${article.title}`"
                                        loading="lazy"
                                        decoding="async"
                                        class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                                    />
                                    <div v-else aria-hidden="true" class="article-placeholder absolute inset-0"></div>
                                    <span
                                        v-if="article.is_premium"
                                        class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-amber-300 px-2.5 py-1 text-[0.65rem] font-black tracking-wide text-amber-950 uppercase shadow"
                                    >
                                        <Crown class="size-3" aria-hidden="true" /> Premium
                                    </span>
                                </div>
                                <div class="flex flex-col p-5 sm:p-6">
                                    <div
                                        class="flex flex-wrap items-center gap-2 text-xs font-bold tracking-[0.1em] text-[#001C55] uppercase dark:text-[#A6E1FA]"
                                    >
                                        <Link
                                            :href="route('games.show', article.game.slug)"
                                            class="hover:underline focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:outline-none"
                                        >
                                            {{ article.game.title }}
                                        </Link>
                                        <span aria-hidden="true">•</span>
                                        <span class="text-slate-400 dark:text-white/45">{{ formatDate(article.published_at) ?? 'Bientôt' }}</span>
                                    </div>
                                    <h3 class="mt-3 text-xl leading-tight font-black sm:text-2xl">
                                        <Link
                                            :href="route('articles.show', article.slug)"
                                            class="text-[#001C55] transition hover:text-[#075985] focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:outline-none dark:text-white dark:hover:text-[#A6E1FA]"
                                        >
                                            {{ article.title }}
                                        </Link>
                                    </h3>
                                    <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-slate-600 dark:text-white/65">
                                        {{ article.excerpt }}
                                    </p>
                                    <div class="mt-auto flex items-center justify-between gap-3 pt-5">
                                        <p class="truncate text-xs text-slate-500 dark:text-white/50">
                                            {{ article.author.name ?? article.author.username }}
                                        </p>
                                        <Link
                                            :href="route('articles.show', article.slug)"
                                            class="inline-flex items-center gap-1.5 text-sm font-bold text-[#0E6BA8] hover:underline focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:outline-none dark:text-[#A6E1FA]"
                                        >
                                            Lire <ArrowRight class="size-4" aria-hidden="true" />
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section aria-labelledby="recent-games-title">
                    <div class="mb-5 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold tracking-[0.18em] text-[#001C55] uppercase dark:text-[#A6E1FA]">Bibliothèque</p>
                            <h2 id="recent-games-title" class="mt-1 text-3xl font-black text-[#001C55] sm:text-4xl dark:text-white">
                                Fraîchement ajoutés
                            </h2>
                        </div>
                        <Link
                            :href="route('games.index')"
                            class="hidden items-center gap-2 rounded-full border border-[#0E6BA8]/25 bg-white/70 px-4 py-2 text-sm font-bold text-[#0E6BA8] transition hover:border-[#0E6BA8] hover:bg-white focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:outline-none sm:inline-flex dark:border-[#A6E1FA]/30 dark:bg-[#001C55]/70 dark:text-[#A6E1FA]"
                        >
                            Tous les jeux <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </div>

                    <div
                        v-if="recentGames.length"
                        class="game-rail -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:mx-0 lg:grid lg:grid-cols-5 lg:overflow-visible lg:px-0"
                        tabindex="0"
                        aria-label="Derniers jeux ajoutés, faites défiler horizontalement"
                    >
                        <Link
                            v-for="game in recentGames"
                            :key="game.id"
                            :href="route('games.show', game.slug)"
                            class="group relative w-[68vw] max-w-64 shrink-0 snap-start overflow-hidden rounded-3xl bg-[#001C55] shadow-[0_18px_45px_-28px_rgba(0,28,85,0.9)] transition duration-300 hover:-translate-y-1 focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transform-none sm:w-56 lg:w-auto"
                        >
                            <div class="aspect-[3/4] overflow-hidden">
                                <img
                                    v-if="gameCoverUrl(game.cover_url)"
                                    :src="gameCoverUrl(game.cover_url) ?? undefined"
                                    :alt="`Jaquette de ${game.title}`"
                                    loading="lazy"
                                    decoding="async"
                                    class="size-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                                />
                                <div v-else class="article-placeholder flex size-full items-center justify-center" aria-hidden="true">
                                    <Gamepad2 class="size-12 text-white/75" />
                                </div>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#00072D] via-[#00072D]/90 to-transparent p-4 pt-16">
                                <h3 class="line-clamp-2 text-lg leading-tight font-black !text-white">{{ game.title }}</h3>
                                <p class="mt-1 text-xs font-semibold text-[#A6E1FA]">
                                    {{ formatDate(game.searched_at) ?? 'Date inconnue' }}
                                </p>
                            </div>
                        </Link>
                    </div>
                    <div
                        v-else
                        class="rounded-3xl border border-dashed border-[#0E6BA8]/30 bg-white/60 p-8 text-center dark:border-[#A6E1FA]/25 dark:bg-[#001C55]/50"
                    >
                        <Gamepad2 class="mx-auto size-8 text-[#0E6BA8] dark:text-[#A6E1FA]" aria-hidden="true" />
                        <h3 class="mt-3 text-lg font-black text-[#001C55] dark:text-white">La bibliothèque se prépare.</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-white/60">Les derniers jeux ajoutés apparaîtront ici.</p>
                    </div>

                    <Link
                        :href="route('games.index')"
                        class="mt-4 inline-flex items-center gap-2 rounded-full border border-[#0E6BA8]/25 bg-white/70 px-4 py-2 text-sm font-bold text-[#0E6BA8] focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:outline-none sm:hidden dark:border-[#A6E1FA]/30 dark:bg-[#001C55]/70 dark:text-[#A6E1FA]"
                    >
                        Tous les jeux <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </section>
            </div>
        </div>
    </AppHeaderLayout>
</template>

<style scoped>
.dashboard-surface {
    background:
        linear-gradient(rgba(14, 107, 168, 0.045) 1px, transparent 1px), linear-gradient(90deg, rgba(14, 107, 168, 0.045) 1px, transparent 1px),
        radial-gradient(circle at 10% 0%, rgba(166, 225, 250, 0.22), transparent 36%), var(--background);
    background-size:
        42px 42px,
        42px 42px,
        auto,
        auto;
}

.dashboard-glow {
    position: absolute;
    width: 22rem;
    height: 22rem;
    border-radius: 9999px;
    background: rgba(14, 107, 168, 0.16);
    filter: blur(80px);
    pointer-events: none;
}

.dashboard-glow--top {
    top: 12rem;
    right: -12rem;
}

.dashboard-glow--bottom {
    bottom: 8rem;
    left: -14rem;
    background: rgba(166, 225, 250, 0.24);
}

.article-placeholder {
    background: radial-gradient(circle at 70% 20%, rgba(166, 225, 250, 0.3), transparent 28%), linear-gradient(135deg, #0e6ba8, #001c55 55%, #00072d);
}

.game-rail {
    scrollbar-width: thin;
    scrollbar-color: rgba(14, 107, 168, 0.55) transparent;
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-surface *,
    .dashboard-surface *::before,
    .dashboard-surface *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>
