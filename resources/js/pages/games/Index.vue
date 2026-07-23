<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { gameCoverUrl } from '@/lib/game-images';
import { type BreadcrumbItem } from '@/types';

import { Head, Link as InertiaLink, router, usePage } from '@inertiajs/vue3';

import { computed, onMounted, ref, watch } from 'vue';

const Link = InertiaLink;

interface GameItem {
    id: number;
    title: string;
    slug: string;
    cover_url: string | null;
    summary: string | null;
    storyline: string | null;
    description: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedGames {
    data: GameItem[];
    links: PaginationLink[];
    meta: {
        current_page: number;
        last_page: number;
    };
}

const props = defineProps<{
    games: PaginatedGames;
    searchQuery?: string | null;
    searchMessage?: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Jeux',
        href: '/games',
    },
];

type LanguageCode = 'fr' | 'en';

interface LanguageOption {
    value: LanguageCode;
    label: string;
    flag: string;
}

const languages: LanguageOption[] = [
    { value: 'fr', label: 'Français', flag: '🇫🇷' },
    { value: 'en', label: 'English', flag: '🇬🇧' },
];

const page = usePage<{ activeLanguage?: LanguageCode; searchQuery?: string | null }>();

const selectedLanguage = ref<LanguageCode>(page.props.activeLanguage ?? 'en');
const searchTerm = ref(props.searchQuery ?? '');
const isSearching = ref(false);

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    const storedLanguage = window.localStorage.getItem('levelup_language');

    if (storedLanguage === 'fr' || storedLanguage === 'en') {
        if (storedLanguage !== selectedLanguage.value) {
            selectedLanguage.value = storedLanguage;
        }
    } else {
        window.localStorage.setItem('levelup_language', selectedLanguage.value);
    }
});

watch(selectedLanguage, (language, previousLanguage) => {
    if (language === previousLanguage) {
        return;
    }

    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem('levelup_language', language);

    if (page.props.activeLanguage === language) {
        return;
    }

    router.visit(
        route('games.index', {
            lang: language,
            search: searchTerm.value.trim() === '' ? undefined : searchTerm.value.trim(),
        }),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['games', 'activeLanguage', 'searchQuery', 'searchMessage'],
        },
    );
});

watch(
    () => page.props.activeLanguage,
    (language) => {
        if (!language || language === selectedLanguage.value) {
            return;
        }

        selectedLanguage.value = language;
    },
);

const selectedLanguageConfig = computed(() => {
    return languages.find((language) => language.value === selectedLanguage.value) ?? languages[0];
});

const pageText = computed(() => {
    if (selectedLanguage.value === 'fr') {
        return {
            headTitle: 'Jeux',
            title: 'Liste des jeux',
            languageLabel: 'Langue',
            languageNotice: 'Choisissez la langue de navigation pour personnaliser votre expérience.',
        } as const;
    }

    return {
        headTitle: 'Games',
        title: 'Games catalog',
        languageLabel: 'Language',
        languageNotice: 'Pick your preferred browsing language to personalise the experience.',
    } as const;
});

const games = computed(() => props.games);
const searchMessage = computed(() => props.searchMessage ?? null);
const hasActiveSearch = computed(() => searchTerm.value.trim().length > 0);

watch(
    () => props.searchQuery,
    (value) => {
        if ((value ?? '') === searchTerm.value) {
            return;
        }

        searchTerm.value = value ?? '';
    },
);

const submitSearch = () => {
    const query = searchTerm.value.trim();
    const normalized = query === '' ? null : query;
    const currentQuery = props.searchQuery ?? null;

    if (normalized === currentQuery) {
        return;
    }

    isSearching.value = true;

    router.visit(
        route('games.index', {
            lang: selectedLanguage.value,
            search: normalized ?? undefined,
        }),
        {
            preserveScroll: true,
            replace: true,
            only: ['games', 'activeLanguage', 'searchQuery', 'searchMessage'],
            onFinish: () => {
                isSearching.value = false;
            },
        },
    );
};

const clearSearch = () => {
    if (searchTerm.value === '') {
        return;
    }

    searchTerm.value = '';
    submitSearch();
};
</script>

<template>
    <Head :title="pageText.headTitle" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl px-4 py-10">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-3xl font-bold text-[#001C55] dark:text-[#A6E1FA]">{{ pageText.title }}</h1>
                <div class="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-3">
                    <span class="text-sm font-medium text-[#0E6BA8] dark:text-white">{{ pageText.languageLabel }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl leading-none" aria-hidden="true">{{ selectedLanguageConfig.flag }}</span>
                        <select
                            v-model="selectedLanguage"
                            class="rounded-lg border border-[#0E6BA8] bg-white px-3 py-2 text-sm font-medium text-[#0E6BA8] shadow-sm focus:border-[#0E6BA8] focus:ring-2 focus:ring-[#0E6BA8]/60 focus:outline-none dark:border-[#A6E1FA] dark:bg-[#001C55] dark:text-white"
                            :aria-label="pageText.languageLabel"
                        >
                            <option v-for="language in languages" :key="language.value" :value="language.value">
                                {{ language.flag }} {{ language.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <p class="mb-8 text-sm text-[#0E6BA8] dark:text-white/80">{{ pageText.languageNotice }}</p>

            <form class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center" @submit.prevent="submitSearch">
                <label class="sr-only" for="game-search">Rechercher un jeu</label>
                <input
                    id="game-search"
                    v-model="searchTerm"
                    type="search"
                    name="search"
                    :placeholder="selectedLanguage.value === 'fr' ? 'Rechercher un jeu…' : 'Search a game…'"
                    class="flex-1 rounded-lg border border-[#0E6BA8] bg-white px-3 py-2 text-sm text-[#0E6BA8] shadow-sm focus:border-[#0E6BA8] focus:ring-2 focus:ring-[#0E6BA8]/60 focus:outline-none dark:border-[#A6E1FA] dark:bg-[#001C55] dark:text-white"
                    :disabled="isSearching"
                />
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="bg-primary hover:bg-primary/90 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow transition disabled:cursor-not-allowed disabled:opacity-70"
                        :disabled="isSearching"
                    >
                        {{ selectedLanguage.value === 'fr' ? 'Rechercher' : 'Search' }}
                    </button>
                    <button
                        v-if="hasActiveSearch"
                        type="button"
                        class="inline-flex items-center rounded-lg border border-[#0E6BA8] px-4 py-2 text-sm font-semibold text-[#0E6BA8] transition hover:bg-[#0E6BA8]/10 dark:border-[#A6E1FA] dark:text-white dark:hover:bg-[#0E6BA8]/30"
                        @click="clearSearch"
                    >
                        {{ selectedLanguage.value === 'fr' ? 'Réinitialiser' : 'Reset' }}
                    </button>
                </div>
            </form>

            <p
                v-if="searchMessage"
                class="mb-6 rounded-lg border border-[#0E6BA8]/40 bg-[#A6E1FA]/40 px-4 py-3 text-sm text-[#001C55] dark:border-[#A6E1FA]/40 dark:bg-[#001C55]/80 dark:text-white"
            >
                {{ searchMessage }}
            </p>
            <p
                v-else-if="hasActiveSearch && games.data.length === 0"
                class="mb-6 rounded-lg border border-dashed border-[#0E6BA8]/60 px-4 py-3 text-sm text-[#0E6BA8] dark:border-[#A6E1FA]/60 dark:text-white"
            >
                {{
                    selectedLanguage.value === 'fr' ? 'Aucun jeu ne correspond à votre recherche pour le moment.' : 'No game matches your search yet.'
                }}
            </p>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="game in games.data" :key="game.id" class="flex h-full flex-col rounded-lg bg-[#A6E1FA] p-4 shadow dark:bg-[#001C55]">
                    <img
                        v-if="gameCoverUrl(game.cover_url)"
                        :src="gameCoverUrl(game.cover_url) ?? undefined"
                        alt=""
                        class="mb-4 h-auto w-full rounded"
                    />
                    <Link
                        :href="route('games.show', { slug: game.slug, lang: selectedLanguage })"
                        class="text-xl font-semibold text-[#001C55] hover:underline dark:text-[#A6E1FA]"
                    >
                        {{ game.title }}
                    </Link>

                    <p
                        v-if="game.storyline || game.summary || game.description"
                        class="mt-2 text-sm text-[#0E6BA8] dark:text-white"
                        style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; min-height: 4.5rem"
                    >
                        {{ game.storyline ?? game.summary ?? game.description }}
                    </p>
                </div>
            </div>

            <nav v-if="games.meta?.last_page > 1" class="mt-10 flex justify-center">
                <ul class="flex items-center gap-2">
                    <li v-for="link in games.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-state
                            preserve-scroll
                            class="inline-flex items-center rounded-md border px-3 py-1 text-sm font-medium transition"
                            :class="[
                                link.active
                                    ? 'border-[#0E6BA8] bg-[#0E6BA8] text-white dark:border-[#A6E1FA] dark:bg-[#0E6BA8] dark:text-white'
                                    : 'border-[#0E6BA8] bg-white text-[#0E6BA8] hover:bg-[#A6E1FA]/40 dark:border-[#A6E1FA] dark:bg-[#001C55] dark:text-white dark:hover:bg-[#0E6BA8]/30',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="inline-flex items-center rounded-md border border-[#0E6BA8]/40 px-3 py-1 text-sm font-medium text-[#0E6BA8]/60 dark:border-[#A6E1FA]/40 dark:text-white/60"
                            v-html="link.label"
                        />
                    </li>
                </ul>
            </nav>
        </div>
    </AppHeaderLayout>
</template>
