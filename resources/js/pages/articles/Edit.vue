<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Accueil', href: '/' },
    { title: 'Jeux', href: '/games' },
    { title: props.game.title, href: route('games.show', props.game.slug) },
    { title: props.mode === 'create' ? 'Nouvel article' : 'Modifier', href: '#' },
]);

const pageTitle = computed(() => (props.mode === 'create' ? 'Rediger un article' : 'Modifier l article'));

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
        <div class="mx-auto max-w-4xl px-4 py-10">
            <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-primary">{{ game.title }}</p>
                    <h1 class="text-3xl font-bold text-[#001C55] dark:text-[#A6E1FA]">{{ pageTitle }}</h1>
                </div>
                <Link :href="route('games.show', game.slug)" class="text-sm font-semibold text-primary hover:underline">
                    Retour au jeu
                </Link>
            </header>

            <form class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900" @submit.prevent="submit">
                <div class="space-y-2">
                    <label for="title" class="text-sm font-semibold">Titre</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-950"
                    />
                    <p v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</p>
                </div>

                <div class="space-y-2">
                    <label for="content" class="text-sm font-semibold">Contenu</label>
                    <textarea
                        id="content"
                        v-model="form.content"
                        rows="14"
                        class="w-full resize-y rounded-lg border border-gray-300 px-3 py-2 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-950"
                    />
                    <p v-if="form.errors.content" class="text-sm text-red-500">{{ form.errors.content }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="keywords" class="text-sm font-semibold">Mots-cles</label>
                        <input
                            id="keywords"
                            v-model="form.keywords"
                            type="text"
                            placeholder="RPG, test, guide"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-950"
                        />
                        <p v-if="form.errors.keywords" class="text-sm text-red-500">{{ form.errors.keywords }}</p>
                    </div>

                    <div class="space-y-2">
                        <label for="published_at" class="text-sm font-semibold">Date de publication</label>
                        <input
                            id="published_at"
                            v-model="form.published_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:border-neutral-700 dark:bg-neutral-950"
                        />
                        <p v-if="form.errors.published_at" class="text-sm text-red-500">{{ form.errors.published_at }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="images" class="text-sm font-semibold">Images</label>
                    <input
                        id="images"
                        type="file"
                        accept="image/*"
                        multiple
                        class="block w-full text-sm"
                        @change="onFilesChange"
                    />
                    <p v-if="form.errors.images" class="text-sm text-red-500">{{ form.errors.images }}</p>
                    <div v-if="article?.images?.length" class="grid gap-3 sm:grid-cols-3">
                        <img v-for="image in article.images" :key="image" :src="image" alt="" class="h-28 w-full rounded-lg object-cover" />
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-neutral-800">
                    <input v-model="form.is_premium" type="checkbox" class="size-4 rounded border-gray-300" />
                    <span>Article payant reserve aux abonnes</span>
                </label>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white shadow transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{ mode === 'create' ? 'Publier' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </AppHeaderLayout>
</template>
