<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { fetchDashboardStats, type DashboardStats } from '@/lib/stats';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { MessageSquare, Megaphone, ShieldCheck, UserMinus } from 'lucide-vue-next';
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

const page = usePage<SharedData & { recentGames?: RecentDashboardGame[]; stats?: DashboardStats }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
];

const isAdmin = computed(() => page.props.auth.user?.is_admin ?? false);

const recentGames = computed(() => page.props.recentGames ?? []);
const hasRecentGames = computed(() => recentGames.value.length > 0);

const adminLinks = computed<AdminAction[]>(() => [
    {
        title: 'Modération',
        description: 'Gère les commentaires et les astuces en attente de validation.',
        href: route('admin.moderation.index'),
        icon: MessageSquare,
    },
    {
        title: 'Pouvoirs',
        description: 'Donne ou retire les droits administrateur aux membres de l’équipe.',
        href: route('admin.powers.index'),
        icon: ShieldCheck,
    },
    {
        title: 'Annonces',
        description: 'Publie des messages importants pour informer toute la communauté.',
        href: route('admin.announcements.index'),
        icon: Megaphone,
    },
    {
        title: 'Demandes RGPD',
        description: 'Traite les requêtes de suppression de compte et de données personnelles.',
        href: route('admin.privacy.requests.index'),
        icon: UserMinus,
    },
]);

const currentAnnouncement = computed(() => page.props.announcement ?? null);

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
        console.error('Unable to load dashboard statistics', error);
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
    value == null ? '—' : value.toLocaleString('fr-FR');

const formatAverage = (value: number | null | undefined) =>
    value == null
        ? '—'
        : value.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
</script>

<template>
    <Head title="Dashboard" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 rounded-xl p-4 md:p-6">
            <section
                v-if="currentAnnouncement"
                class="space-y-3 rounded-xl border border-blue-200 bg-blue-50 p-6 text-blue-900 shadow-sm dark:border-blue-800/60 dark:bg-blue-950/60 dark:text-blue-100"
            >
                <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-300">
                            Information du site
                        </p>
                        <h2 class="text-lg font-semibold">{{ currentAnnouncement.title }}</h2>
                    </div>
                    <div class="text-xs text-blue-700/80 dark:text-blue-200/80">
                        <p v-if="currentAnnouncement.author">
                            Posté par
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
                class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
            >
                <header>
                    <h2 class="text-lg font-semibold">Menu administrateur</h2>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">
                        Accède rapidement aux outils nécessaires pour modérer la communauté et gérer les accès.
                    </p>
                </header>

                <div class="grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="link in adminLinks"
                        :key="link.title"
                        :href="link.href"
                        class="group flex items-start justify-between gap-4 rounded-lg border border-gray-200 bg-white p-5 transition hover:border-primary hover:shadow-md dark:border-neutral-800 dark:bg-neutral-900"
                    >
                        <div class="flex items-start gap-4">
                            <span
                                class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white dark:bg-primary/20"
                            >
                                <component :is="link.icon" class="size-5" />
                            </span>
                            <div class="space-y-1">
                                <h3 class="text-sm font-semibold">{{ link.title }}</h3>
                                <p class="text-xs text-gray-600 dark:text-neutral-400">{{ link.description }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-primary transition group-hover:underline">Accéder</span>
                    </Link>
                </div>
            </section>

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <section
                    class="flex h-full flex-col gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <header class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">
                            Tendances de la communauté
                        </p>
                        <h2 class="text-lg font-semibold">Derniers jeux recherchés</h2>
                        <p class="text-sm text-gray-600 dark:text-neutral-400">
                            Voici les titres récemment découverts par les membres de LevelUp.
                        </p>
                    </header>

                    <div class="flex flex-1 flex-col justify-between gap-4">
                        <ul v-if="hasRecentGames" class="flex flex-1 flex-col gap-4">
                            <li v-for="game in recentGames" :key="game.id">
                                <Link
                                    :href="route('games.show', game.slug)"
                                    class="group flex items-center gap-4 rounded-lg border border-gray-200 p-3 transition hover:border-primary/60 hover:bg-primary/5 dark:border-neutral-800 dark:hover:border-primary/60"
                                >
                                    <div
                                        class="relative flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-md bg-gray-100 text-xs font-semibold uppercase text-gray-500 dark:bg-neutral-800 dark:text-neutral-400"
                                    >
                                        <img
                                            v-if="game.cover_url"
                                            :src="game.cover_url"
                                            :alt="`Jaquette de ${game.title}`"
                                            class="size-full object-cover"
                                            loading="lazy"
                                        />
                                        <span v-else>{{ game.title.slice(0, 2) }}</span>
                                    </div>
                                    <div class="flex flex-1 flex-col gap-1">
                                        <span class="text-sm font-semibold text-gray-900 transition group-hover:text-primary dark:text-neutral-100">
                                            {{ game.title }}
                                        </span>
                                        <span class="text-xs text-gray-600 dark:text-neutral-400">
                                            Recherché le
                                            <span class="font-medium">{{ formatDate(game.searched_at) ?? '—' }}</span>
                                        </span>
                                    </div>
                                    <span class="text-xs font-semibold text-primary transition group-hover:underline">Voir</span>
                                </Link>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-gray-600 dark:text-neutral-400">
                            Aucune recherche récente pour le moment. Explorez la bibliothèque de jeux pour inspirer la communauté !
                        </p>
                        <div class="flex items-center justify-between text-xs text-white dark:text-neutral-500">
                            <span>Mis à jour en continu</span>
                            <Link :href="route('games.index')" class="font-semibold text-primary transition hover:underline">
                                Voir la bibliothèque
                            </Link>
                        </div>
                    </div>
                </section>
                <section
                    class="flex h-full flex-col gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <header class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Vue d'ensemble</p>
                        <h2 class="text-lg font-semibold">Dynamique de la bibliothèque</h2>
                        <p class="text-sm text-gray-600 dark:text-neutral-400">
                            Surveille la croissance des jeux référencés et l’activité des évaluations.
                        </p>
                    </header>

                    <div class="flex flex-1 flex-col gap-4">
                        <div
                            v-if="isStatsLoading"
                            class="flex flex-1 items-center justify-center text-sm text-gray-600 dark:text-neutral-400"
                        >
                            Chargement des statistiques…
                        </div>
                        <div
                            v-else-if="statsError"
                            class="flex flex-1 items-center justify-center text-center text-sm text-red-600 dark:text-red-400"
                        >
                            {{ statsError }}
                        </div>
                        <dl
                            v-else-if="stats"
                            class="grid flex-1 gap-4 sm:grid-cols-2"
                        >
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Jeux disponibles</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatNumber(stats.games.total) }}
                                </dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">
                                    Jeux ayant reçu une note
                                </dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatNumber(stats.games.rated_total) }}
                                </dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Notes partagées</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatNumber(stats.ratings.total) }}
                                </dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">
                                    Note moyenne globale
                                </dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatAverage(stats.ratings.average) }} / 5
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-else
                            class="text-sm text-gray-600 dark:text-neutral-400"
                        >
                            Aucune statistique disponible pour le moment.
                        </p>
                    </div>
                </section>
                <section
                    class="flex h-full flex-col gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                >
                    <header class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">Engagement</p>
                        <h2 class="text-lg font-semibold">Contributions de la communauté</h2>
                        <p class="text-sm text-gray-600 dark:text-neutral-400">
                            Évalue la participation des membres à travers les astuces, commentaires et inscriptions.
                        </p>
                    </header>

                    <div class="flex flex-1 flex-col gap-4">
                        <div
                            v-if="isStatsLoading"
                            class="flex flex-1 items-center justify-center text-sm text-gray-600 dark:text-neutral-400"
                        >
                            Chargement des statistiques…
                        </div>
                        <div
                            v-else-if="statsError"
                            class="flex flex-1 items-center justify-center text-center text-sm text-red-600 dark:text-red-400"
                        >
                            {{ statsError }}
                        </div>
                        <dl
                            v-else-if="stats"
                            class="grid flex-1 gap-4 sm:grid-cols-2"
                        >
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">
                                    Commentaires approuvés
                                </dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatNumber(stats.comments.approved_total) }}
                                </dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">
                                    Astuces approuvées
                                </dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatNumber(stats.tips.approved_total) }}
                                </dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-neutral-800/50 sm:col-span-2">
                                <dt class="text-xs font-medium uppercase text-gray-600 dark:text-neutral-400">Membres inscrits</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-neutral-100">
                                    {{ formatNumber(stats.users.total) }}
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-else
                            class="text-sm text-gray-600 dark:text-neutral-400"
                        >
                            Les contributions apparaîtront ici dès que la communauté partage du contenu.
                        </p>
                    </div>
                </section>
            </div>
            <section
                class="flex min-h-[100vh] flex-1 flex-col gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 md:min-h-min"
            >
                <header class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-primary">Synthèse</p>
                    <h2 class="text-lg font-semibold">Vue générale de la plateforme</h2>
                    <p class="text-sm text-gray-600 dark:text-neutral-400">
                        Un aperçu rapide des indicateurs clés pour suivre l’évolution de LevelUp.
                    </p>
                </header>

                <div class="flex flex-1 flex-col gap-4">
                    <div
                        v-if="isStatsLoading"
                        class="flex flex-1 items-center justify-center text-sm text-gray-600 dark:text-neutral-400"
                    >
                        Chargement des statistiques…
                    </div>
                    <div
                        v-else-if="statsError"
                        class="flex flex-1 items-center justify-center text-center text-sm text-red-600 dark:text-red-400"
                    >
                        {{ statsError }}
                    </div>
                    <div v-else-if="stats" class="flex flex-1 flex-col justify-between gap-6">
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-neutral-300">
                            La bibliothèque compte
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatNumber(stats.games.total) }} jeux référencés
                            </span>
                            dont
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatNumber(stats.games.rated_total) }} titres évalués
                            </span>
                            par la communauté.
                        </p>
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-neutral-300">
                            Les membres ont déposé
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatNumber(stats.ratings.total) }} notes
                            </span>
                            pour une moyenne globale de
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatAverage(stats.ratings.average) }} / 5
                            </span>
                            , complétées par
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatNumber(stats.comments.approved_total) }} commentaires
                            </span>
                            et
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatNumber(stats.tips.approved_total) }} astuces
                            </span>
                            déjà partagés.
                        </p>
                        <p class="text-sm leading-relaxed text-gray-700 dark:text-neutral-300">
                            La communauté s’agrandit chaque jour avec
                            <span class="font-semibold text-gray-900 dark:text-neutral-100">
                                {{ formatNumber(stats.users.total) }} membres inscrits
                            </span>
                            prêts à découvrir de nouvelles expériences de jeu.
                        </p>
                    </div>
                    <p v-else class="text-sm text-gray-600 dark:text-neutral-400">
                        Revenez plus tard pour consulter les indicateurs du tableau de bord.
                    </p>
                </div>
            </section>
        </div>
    </AppHeaderLayout>
</template>
