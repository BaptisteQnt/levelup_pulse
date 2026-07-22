<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { getCsrfToken, getXsrfToken } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    ExternalLink,
    Lightbulb,
    LoaderCircle,
    RotateCcw,
    Search,
    Sparkles,
    WandSparkles,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface TrendingGame {
    title: string;
    why_trending: string;
    article_angle: string;
}

interface TrendSource {
    title: string;
    url: string;
}

interface TrendingResponse {
    games: TrendingGame[];
    sources: TrendSource[];
    generated_at: string;
}

interface CorrectionResponse {
    corrected_text: string;
    changes: string[];
    editorial_notes: string[];
}

const props = defineProps<{
    mode: 'create' | 'edit';
    game: {
        id: number;
        title: string;
        slug: string;
        cover_url: string | null;
    };
    article: {
        id: number;
        slug: string;
        title: string;
        content: string;
        keywords: string;
        is_premium: boolean;
        published_at: string | null;
        images: string[];
    } | null;
}>();

const form = useForm({
    title: props.article?.title ?? '',
    content: props.article?.content ?? '',
    keywords: props.article?.keywords ?? '',
    is_premium: props.article?.is_premium ?? false,
    published_at: props.article?.published_at ?? new Date().toISOString().slice(0, 16),
    images: [] as File[],
});

const trendingGames = ref<TrendingGame[]>([]);
const trendSources = ref<TrendSource[]>([]);
const trendsGeneratedAt = ref<string | null>(null);
const trendsLoading = ref(false);
const trendsError = ref<string | null>(null);

const correction = ref<CorrectionResponse | null>(null);
const correctionLoading = ref(false);
const correctionError = ref<string | null>(null);
const correctionApplied = ref(false);
const contentBeforeCorrection = ref<string | null>(null);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Accueil', href: '/' },
    { title: 'Jeux', href: '/games' },
    { title: props.game.title, href: route('games.show', props.game.slug) },
    { title: props.mode === 'create' ? 'Nouvel article' : 'Modifier', href: '#' },
]);

const pageTitle = computed(() =>
    props.mode === 'create' ? 'Rédiger un article' : 'Modifier l’article',
);

const canRequestCorrection = computed(() => {
    const length = form.content.trim().length;
    return length >= 20 && length <= 30000 && !correctionLoading.value;
});

const formatAiDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : null;

const postJson = async <T>(url: string, payload: Record<string, unknown> = {}): Promise<T> => {
    const csrfToken = getCsrfToken();
    const xsrfToken = getXsrfToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
            ...(xsrfToken && { 'X-XSRF-TOKEN': xsrfToken }),
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    const data = (await response.json().catch(() => ({}))) as T & { message?: string };

    if (!response.ok) {
        throw new Error(data.message ?? 'L’assistant IA n’a pas pu répondre.');
    }

    return data;
};

const loadTrendingGames = async () => {
    trendsLoading.value = true;
    trendsError.value = null;

    try {
        const result = await postJson<TrendingResponse>(route('articles.ai.trending'));
        trendingGames.value = result.games;
        trendSources.value = result.sources;
        trendsGeneratedAt.value = result.generated_at;
    } catch (error) {
        trendsError.value =
            error instanceof Error ? error.message : 'Impossible de charger les tendances.';
    } finally {
        trendsLoading.value = false;
    }
};

const requestCorrection = async () => {
    if (!canRequestCorrection.value) {
        correctionError.value =
            form.content.trim().length < 20
                ? 'Écrivez au moins 20 caractères avant de demander une correction.'
                : 'Le texte doit contenir au maximum 30 000 caractères pour la correction IA.';
        return;
    }

    correctionLoading.value = true;
    correctionError.value = null;
    correctionApplied.value = false;

    try {
        correction.value = await postJson<CorrectionResponse>(
            route('articles.ai.correct', props.game.id),
            {
                title: form.title || null,
                content: form.content,
            },
        );
    } catch (error) {
        correctionError.value =
            error instanceof Error ? error.message : 'Impossible de corriger le brouillon.';
    } finally {
        correctionLoading.value = false;
    }
};

const applyCorrection = () => {
    if (!correction.value) {
        return;
    }

    contentBeforeCorrection.value = form.content;
    form.content = correction.value.corrected_text;
    correction.value = null;
    correctionApplied.value = true;
};

const undoCorrection = () => {
    if (contentBeforeCorrection.value === null) {
        return;
    }

    form.content = contentBeforeCorrection.value;
    contentBeforeCorrection.value = null;
    correctionApplied.value = false;
};

const dismissCorrection = () => {
    correction.value = null;
    correctionError.value = null;
};

const onFilesChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.images = Array.from(input.files ?? []);
};

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('articles.store', props.game.id), {
            forceFormData: true,
        });
        return;
    }

    if (!props.article) {
        return;
    }

    router.post(
        route('articles.update', props.article.slug),
        {
            _method: 'patch',
            title: form.title,
            content: form.content,
            keywords: form.keywords,
            is_premium: form.is_premium,
            published_at: form.published_at,
            images: form.images,
        },
        {
            forceFormData: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <header class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#075985] dark:text-[#A6E1FA]">
                        {{ game.title }}
                    </p>
                    <h1 class="mt-1 text-3xl font-black text-[#001C55] sm:text-4xl dark:text-white">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-white/65">
                        Gardez la maîtrise éditoriale : l’IA propose, vous décidez de ce qui rejoint l’article.
                    </p>
                </div>
                <Link
                    :href="route('games.show', game.slug)"
                    class="text-sm font-bold text-[#075985] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0E6BA8] dark:text-[#A6E1FA]"
                >
                    Retour au jeu
                </Link>
            </header>

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
                <form
                    class="space-y-6 rounded-3xl border border-[#0E6BA8]/15 bg-white p-5 shadow-[0_20px_60px_-42px_rgba(0,28,85,0.7)] sm:p-7 lg:col-span-8 dark:border-[#A6E1FA]/15 dark:bg-[#001C55]"
                    @submit.prevent="submit"
                >
                    <div class="space-y-2">
                        <label for="title" class="text-sm font-bold text-[#001C55] dark:text-white">Titre</label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#0E6BA8] focus:outline-none focus:ring-2 focus:ring-[#0E6BA8]/30 dark:border-white/20 dark:bg-[#00072D] dark:text-white"
                        />
                        <p v-if="form.errors.title" class="text-sm text-red-600 dark:text-red-300">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex flex-wrap items-end justify-between gap-2">
                            <label for="content" class="text-sm font-bold text-[#001C55] dark:text-white">Contenu</label>
                            <span class="text-xs text-slate-500 dark:text-white/50">
                                {{ form.content.length.toLocaleString('fr-FR') }} caractères
                            </span>
                        </div>
                        <textarea
                            id="content"
                            v-model="form.content"
                            rows="18"
                            class="w-full resize-y rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm leading-relaxed text-slate-900 focus:border-[#0E6BA8] focus:outline-none focus:ring-2 focus:ring-[#0E6BA8]/30 dark:border-white/20 dark:bg-[#00072D] dark:text-white"
                        />
                        <p v-if="form.errors.content" class="text-sm text-red-600 dark:text-red-300">
                            {{ form.errors.content }}
                        </p>

                        <section
                            class="mt-3 overflow-hidden rounded-2xl border border-[#0E6BA8]/20 bg-[#EAF7FC] dark:border-[#A6E1FA]/20 dark:bg-[#00072D]/70"
                            aria-labelledby="correction-assistant-title"
                        >
                            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#0E6BA8] text-white">
                                        <WandSparkles class="size-5" aria-hidden="true" />
                                    </span>
                                    <div>
                                        <h2 id="correction-assistant-title" class="text-base font-black text-[#001C55] dark:text-white">
                                            Assistant de correction
                                        </h2>
                                        <p class="mt-1 max-w-xl text-xs leading-relaxed text-slate-600 dark:text-white/60">
                                            Corrige la langue et la fluidité sans ajouter de faits ni remplacer automatiquement votre texte.
                                        </p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    :disabled="!canRequestCorrection"
                                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-[#001C55] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0E6BA8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#A6E1FA] dark:text-[#001C55] dark:hover:bg-white"
                                    @click="requestCorrection"
                                >
                                    <LoaderCircle v-if="correctionLoading" class="size-4 animate-spin" aria-hidden="true" />
                                    <WandSparkles v-else class="size-4" aria-hidden="true" />
                                    {{ correctionLoading ? 'Correction en cours…' : 'Corriger mon texte' }}
                                </button>
                            </div>

                            <p class="border-t border-[#0E6BA8]/10 px-4 py-2.5 text-[0.7rem] leading-relaxed text-slate-500 dark:border-white/10 dark:text-white/50">
                                Le brouillon est envoyé à OpenAI uniquement lors du clic. La réponse n’est ni appliquée automatiquement ni enregistrée par cette fonctionnalité.
                            </p>

                            <p v-if="correctionError" class="border-t border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-400/20 dark:bg-red-950/30 dark:text-red-200" role="alert">
                                {{ correctionError }}
                            </p>

                            <div v-if="correctionApplied" class="flex flex-wrap items-center justify-between gap-3 border-t border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-950/30 dark:text-emerald-200" role="status">
                                <span class="inline-flex items-center gap-2 font-bold">
                                    <Check class="size-4" aria-hidden="true" /> Proposition appliquée au brouillon.
                                </span>
                                <button type="button" class="inline-flex items-center gap-1.5 font-bold underline" @click="undoCorrection">
                                    <RotateCcw class="size-4" aria-hidden="true" /> Annuler
                                </button>
                            </div>

                            <div v-if="correction" class="space-y-4 border-t border-[#0E6BA8]/15 bg-white p-4 dark:border-white/10 dark:bg-[#001C55]" aria-live="polite">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="font-black text-[#001C55] dark:text-white">Proposition corrigée</h3>
                                    <button type="button" class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0E6BA8] dark:text-white/60 dark:hover:bg-white/10" aria-label="Fermer la proposition" @click="dismissCorrection">
                                        <X class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                                <textarea
                                    :value="correction.corrected_text"
                                    readonly
                                    rows="14"
                                    class="w-full resize-y rounded-xl border border-[#0E6BA8]/20 bg-slate-50 px-3 py-3 text-sm leading-relaxed text-slate-800 dark:border-white/15 dark:bg-[#00072D] dark:text-white"
                                    aria-label="Texte corrigé proposé par l’assistant"
                                ></textarea>
                                <div v-if="correction.changes.length" class="rounded-xl bg-[#EAF7FC] p-3 dark:bg-[#00072D]/60">
                                    <p class="text-xs font-black uppercase tracking-wide text-[#001C55] dark:text-[#A6E1FA]">Principales corrections</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-slate-600 dark:text-white/65">
                                        <li v-for="change in correction.changes" :key="change">{{ change }}</li>
                                    </ul>
                                </div>
                                <div v-if="correction.editorial_notes.length" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-900 dark:border-amber-400/20 dark:bg-amber-950/25 dark:text-amber-100">
                                    <p class="text-xs font-black uppercase tracking-wide">Points à vérifier</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                                        <li v-for="note in correction.editorial_notes" :key="note">{{ note }}</li>
                                    </ul>
                                </div>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-white/20 dark:text-white dark:hover:bg-white/10" @click="dismissCorrection">
                                        Conserver mon texte
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-full bg-[#001C55] px-4 py-2 text-sm font-bold text-white hover:bg-[#0E6BA8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0E6BA8] dark:bg-[#A6E1FA] dark:text-[#001C55] dark:hover:bg-white" @click="applyCorrection">
                                        <Check class="size-4" aria-hidden="true" /> Appliquer la correction
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="keywords" class="text-sm font-bold text-[#001C55] dark:text-white">Mots-clés</label>
                            <input
                                id="keywords"
                                v-model="form.keywords"
                                type="text"
                                placeholder="RPG, test, guide"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#0E6BA8] focus:outline-none focus:ring-2 focus:ring-[#0E6BA8]/30 dark:border-white/20 dark:bg-[#00072D] dark:text-white"
                            />
                            <p v-if="form.errors.keywords" class="text-sm text-red-600 dark:text-red-300">
                                {{ form.errors.keywords }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label for="published_at" class="text-sm font-bold text-[#001C55] dark:text-white">Date de publication</label>
                            <input
                                id="published_at"
                                v-model="form.published_at"
                                type="datetime-local"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-[#0E6BA8] focus:outline-none focus:ring-2 focus:ring-[#0E6BA8]/30 dark:border-white/20 dark:bg-[#00072D] dark:text-white"
                            />
                            <p v-if="form.errors.published_at" class="text-sm text-red-600 dark:text-red-300">
                                {{ form.errors.published_at }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="images" class="text-sm font-bold text-[#001C55] dark:text-white">Images</label>
                        <input
                            id="images"
                            type="file"
                            accept="image/*"
                            multiple
                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-[#EAF7FC] file:px-4 file:py-2 file:font-bold file:text-[#001C55] dark:text-white/70 dark:file:bg-[#0E6BA8] dark:file:text-white"
                            @change="onFilesChange"
                        />
                        <p v-if="form.errors.images" class="text-sm text-red-600 dark:text-red-300">
                            {{ form.errors.images }}
                        </p>
                        <div v-if="article?.images?.length" class="grid gap-3 sm:grid-cols-3">
                            <img
                                v-for="image in article.images"
                                :key="image"
                                :src="image"
                                alt=""
                                class="h-28 w-full rounded-xl object-cover"
                            />
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-700 dark:border-white/15 dark:text-white/75">
                        <input v-model="form.is_premium" type="checkbox" class="size-4 rounded border-slate-300" />
                        <span>Article payant réservé aux abonnés</span>
                    </label>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-full bg-[#001C55] px-6 py-2.5 text-sm font-bold text-white shadow transition hover:bg-[#0E6BA8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0E6BA8] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-[#A6E1FA] dark:text-[#001C55] dark:hover:bg-white"
                        >
                            {{ mode === 'create' ? 'Publier' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>

                <aside class="lg:sticky lg:top-6 lg:col-span-4" aria-labelledby="trend-assistant-title">
                    <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-[#0E6BA8] to-[#001C55] text-white shadow-[0_24px_60px_-35px_rgba(0,28,85,0.9)]">
                        <div class="p-5 sm:p-6">
                            <span class="flex size-11 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                                <Sparkles class="size-5" aria-hidden="true" />
                            </span>
                            <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[#A6E1FA]">Veille éditoriale IA</p>
                            <h2 id="trend-assistant-title" class="mt-1 text-2xl font-black !text-white">Sur quels jeux écrire ?</h2>
                            <p class="mt-2 text-sm leading-relaxed text-white/70">
                                OpenAI consulte le web au moment de la demande et propose cinq sujets accompagnés d’un angle éditorial.
                            </p>
                            <button
                                type="button"
                                :disabled="trendsLoading"
                                class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-white px-4 py-3 text-sm font-black text-[#001C55] transition hover:bg-[#A6E1FA] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[#001C55] disabled:cursor-not-allowed disabled:opacity-60"
                                @click="loadTrendingGames"
                            >
                                <LoaderCircle v-if="trendsLoading" class="size-4 animate-spin" aria-hidden="true" />
                                <Search v-else class="size-4" aria-hidden="true" />
                                {{ trendsLoading ? 'Recherche des tendances…' : 'Suggérer 5 jeux tendance' }}
                            </button>
                            <p class="mt-3 text-center text-[0.7rem] leading-relaxed text-white/50">
                                Résultats variables selon l’actualité. Vérifiez toujours les sources avant publication.
                            </p>
                        </div>

                        <p v-if="trendsError" class="border-t border-white/10 bg-red-950/35 px-5 py-4 text-sm text-red-100" role="alert">
                            {{ trendsError }}
                        </p>

                        <div v-if="trendingGames.length" class="border-t border-white/10 bg-[#00072D]/35" aria-live="polite">
                            <ol class="divide-y divide-white/10">
                                <li v-for="(suggestion, index) in trendingGames" :key="suggestion.title" class="p-5">
                                    <div class="flex items-start gap-3">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-[#A6E1FA] text-xs font-black text-[#001C55]">
                                            {{ index + 1 }}
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-base font-black !text-white">{{ suggestion.title }}</h3>
                                            <p class="mt-2 text-xs leading-relaxed text-white/65">{{ suggestion.why_trending }}</p>
                                            <div class="mt-3 rounded-xl border border-[#A6E1FA]/20 bg-[#A6E1FA]/10 p-3">
                                                <p class="flex items-center gap-1.5 text-[0.65rem] font-black uppercase tracking-wide text-[#A6E1FA]">
                                                    <Lightbulb class="size-3.5" aria-hidden="true" /> Angle proposé
                                                </p>
                                                <p class="mt-1 text-xs leading-relaxed text-white/80">{{ suggestion.article_angle }}</p>
                                            </div>
                                            <Link
                                                :href="route('games.index', { search: suggestion.title, lang: 'fr' })"
                                                target="_blank"
                                                rel="noopener"
                                                class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-[#A6E1FA] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#A6E1FA]"
                                            >
                                                Rechercher ce jeu <ExternalLink class="size-3.5" aria-hidden="true" />
                                            </Link>
                                        </div>
                                    </div>
                                </li>
                            </ol>

                            <div v-if="trendSources.length" class="border-t border-white/10 px-5 py-4">
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.14em] text-white/55">Sources consultées</p>
                                <ul class="mt-2 space-y-1.5">
                                    <li v-for="source in trendSources" :key="source.url">
                                        <a
                                            :href="source.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="line-clamp-1 text-xs text-[#A6E1FA] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#A6E1FA]"
                                        >
                                            {{ source.title }}
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <p v-if="formatAiDate(trendsGeneratedAt)" class="border-t border-white/10 px-5 py-3 text-[0.65rem] text-white/45">
                                Veille générée le {{ formatAiDate(trendsGeneratedAt) }}
                            </p>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </AppHeaderLayout>
</template>
