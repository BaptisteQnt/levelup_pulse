<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Information',
        href: '/information',
    },
];
</script>

<template>
    <Head title="Information" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-10">
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">Information</h1>
            <p class="text-base leading-relaxed text-neutral-600 dark:text-neutral-300">
                Bienvenue sur LevelUp. Vous trouverez ici toutes les informations essentielles sur notre plateforme et sur les services que nous proposons aux passionnés de jeux vidéo. Explorez les différentes sections pour découvrir comment profiter pleinement de l'expérience LevelUp.
            </p>
            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Nos engagements</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Mettre en avant les meilleurs jeux du moment avec des fiches détaillées.</li>
                    <li>Proposer des analyses et des recommandations pour guider vos choix.</li>
                    <li>Offrir une communauté accueillante et active autour du jeu vidéo.</li>
                </ul>
            </section>
            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Besoin d'aide ?</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Notre équipe reste disponible pour répondre à vos questions. Contactez-nous via les canaux habituels ou rendez-vous sur votre espace personnel pour accéder au support si vous disposez déjà d'un compte.
                </p>
            </section>
        </div>
    </AppHeaderLayout>
</template>
