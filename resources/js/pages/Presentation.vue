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
        title: 'Présentation',
        href: '/presentation',
    },
];
</script>

<template>
    <Head title="Présentation" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-10">
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">Présentation</h1>
            <p class="text-base leading-relaxed text-neutral-600 dark:text-neutral-300">
                LevelUp est une plateforme dédiée aux joueurs qui souhaitent découvrir de nouveaux titres, suivre l'actualité vidéoludique et partager leur passion. Notre mission est de créer un espace moderne et intuitif pour explorer les jeux qui comptent pour vous.
            </p>
            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Ce que nous proposons</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Un tableau de bord pour suivre les nouveautés et vos jeux favoris.</li>
                    <li>Des fiches détaillées avec captures, résumés et impressions.</li>
                    <li>Une interface soignée accessible sur ordinateur comme sur mobile.</li>
                </ul>
            </section>
            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Rejoignez la communauté</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Créez un compte pour accéder à toutes les fonctionnalités : consulter le catalogue de jeux, laisser des avis et personnaliser votre expérience. Nous avons hâte de vous retrouver en jeu !
                </p>
            </section>
        </div>
    </AppHeaderLayout>
</template>
