<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type ReactionType = 'like' | 'dislike';

const props = defineProps<{
    article: {
        id: number;
        title: string;
        slug: string;
        content: string;
        images: string[];
        keywords: string[];
        is_premium: boolean;
        published_at: string | null;
        likes_count: number;
        dislikes_count: number;
        user_reaction: ReactionType | null;
        author: {
            id: number;
            name: string | null;
            username: string;
        };
        game: {
            id: number;
            title: string;
            slug: string;
            cover_url: string | null;
        };
    };
    canManage: boolean;
    flash?: string | null;
}>();

const page = usePage();
const auth = page.props.auth;
const reacting = ref(false);
const activeImage = ref(0);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Accueil', href: '/' },
    { title: 'Jeux', href: '/games' },
    { title: props.article.game.title, href: route('games.show', props.article.game.slug) },
    { title: props.article.title, href: page.url },
]);

const react = (reaction: ReactionType) => {
    if (!auth.user) {
        return;
    }

    reacting.value = true;

    router.post(
        route('articles.react', props.article.slug),
        { reaction },
        {
            preserveScroll: true,
            onFinish: () => {
                reacting.value = false;
            },
        },
    );
};

const destroyArticle = () => {
    if (!confirm('Supprimer cet article ?')) {
        return;
    }

    router.delete(route('articles.destroy', props.article.slug));
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'long',
              timeStyle: 'short',
          }).format(new Date(value))
        : null;
</script>

<template>
    <Head :title="article.title" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <article class="mx-auto max-w-4xl px-4 py-10">
            <div v-if="flash" class="mb-6 rounded border border-green-300 bg-green-100 px-4 py-2 text-green-800">
                {{ flash }}
            </div>

            <header class="mb-8">
                <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-neutral-400">
                    <Link :href="route('games.show', article.game.slug)" class="font-semibold text-primary hover:underline">
                        {{ article.game.title }}
                    </Link>
                    <span v-if="article.is_premium" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        Premium
                    </span>
                    <span v-if="formatDate(article.published_at)">{{ formatDate(article.published_at) }}</span>
                </div>

                <h1 class="text-4xl font-bold leading-tight text-[#001C55] dark:text-[#A6E1FA]">{{ article.title }}</h1>
                <p class="mt-3 text-sm text-gray-600 dark:text-neutral-400">
                    Par {{ article.author.name ?? article.author.username }}
                </p>

                <div v-if="canManage" class="mt-5 flex gap-3">
                    <Link :href="route('articles.edit', article.slug)" class="rounded-lg border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/10">
                        Modifier
                    </Link>
                    <button type="button" class="rounded-lg border border-red-500 px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50" @click="destroyArticle">
                        Supprimer
                    </button>
                </div>
            </header>

            <section v-if="article.images.length" class="mb-8">
                <img :src="article.images[activeImage]" alt="" class="max-h-[520px] w-full rounded-lg object-cover shadow" />
                <div v-if="article.images.length > 1" class="mt-3 flex gap-2 overflow-x-auto">
                    <button
                        v-for="(image, index) in article.images"
                        :key="image"
                        type="button"
                        class="h-16 w-24 shrink-0 overflow-hidden rounded-md border"
                        :class="index === activeImage ? 'border-primary' : 'border-transparent opacity-70'"
                        @click="activeImage = index"
                    >
                        <img :src="image" alt="" class="size-full object-cover" />
                    </button>
                </div>
            </section>

            <div class="prose max-w-none whitespace-pre-line text-gray-800 dark:prose-invert dark:text-neutral-100">
                {{ article.content }}
            </div>

            <div v-if="article.keywords.length" class="mt-8 flex flex-wrap gap-2">
                <span v-for="keyword in article.keywords" :key="keyword" class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                    {{ keyword }}
                </span>
            </div>

            <footer class="mt-10 flex items-center gap-3 border-t border-gray-200 pt-6 dark:border-neutral-800">
                <button
                    type="button"
                    class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                    :class="article.user_reaction === 'like' ? 'border-primary bg-primary/10 text-primary' : 'border-gray-200 hover:border-primary hover:text-primary'"
                    :disabled="!auth.user || reacting"
                    @click="react('like')"
                >
                    Pouce haut {{ article.likes_count }}
                </button>
                <button
                    type="button"
                    class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                    :class="article.user_reaction === 'dislike' ? 'border-red-500 bg-red-50 text-red-600' : 'border-gray-200 hover:border-red-500 hover:text-red-600'"
                    :disabled="!auth.user || reacting"
                    @click="react('dislike')"
                >
                    Pouce bas {{ article.dislikes_count }}
                </button>
            </footer>
        </article>
    </AppHeaderLayout>
</template>
