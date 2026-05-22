<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Politique des cookies',
        href: '/politique-cookies',
    },
];
</script>

<template>
    <Head title="Politique des cookies" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">Politique des cookies</h1>
                <p class="text-base text-neutral-600 dark:text-neutral-300">
                    Découvrez comment LevelUp utilise les cookies pour faire fonctionner le site, mesurer l'audience et vous
                    permettre de choisir vos préférences en toute transparence.
                </p>
            </header>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">1. Utilisation des cookies</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Lors de votre navigation sur le site, des cookies peuvent être déposés sur votre appareil. Ces cookies
                    servent principalement à :
                </p>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>assurer le bon fonctionnement du site ;</li>
                    <li>mesurer l’audience et améliorer l’expérience utilisateur ;</li>
                    <li>analyser le comportement de navigation (avec votre accord) ;</li>
                    <li>gérer les préférences liées au consentement.</li>
                </ul>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Le site utilise un système de gestion du consentement (TarteAuCitron) permettant d’accepter ou de refuser
                    les cookies non essentiels.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">2. Cookies nécessaires</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Ces cookies sont indispensables au fonctionnement du site. Ils permettent notamment :
                </p>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>la connexion au compte utilisateur ;</li>
                    <li>la sécurisation des formulaires ;</li>
                    <li>la gestion de la session.</li>
                </ul>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Ils ne peuvent pas être désactivés car ils sont requis pour fournir le service.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">3. Cookies de mesure d’audience</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Avec votre accord, des outils de mesure d’audience peuvent être utilisés :
                </p>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Google Analytics, pour comprendre l’activité du site et améliorer son fonctionnement ;</li>
                    <li>Microsoft Clarity, pour analyser les interactions globales des visiteurs.</li>
                </ul>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Ces cookies sont déposés uniquement après votre consentement via le bandeau cookies. La durée maximale de
                    conservation est de 13 mois.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">4. Gestion du consentement</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Lors de votre première visite, un bandeau d’information vous permet :
                </p>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>d’accepter ou refuser tous les cookies ;</li>
                    <li>de choisir vos préférences par service (Analytics, Clarity, etc.) ;</li>
                    <li>de modifier votre choix à tout moment.</li>
                </ul>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Vous pouvez revoir ou modifier votre consentement via l’icône ou le bouton « Gestion des cookies » présent
                    sur le site.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">5. Votre choix à tout moment</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Vous pouvez :
                </p>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>revenir sur votre consentement ;</li>
                    <li>retirer votre accord ;</li>
                    <li>supprimer les cookies via votre navigateur.</li>
                </ul>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Le retrait du consentement n’a aucune incidence sur l’accès au site, hormis la désactivation des
                    fonctionnalités analytiques.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-blue-200/70 bg-blue-50/80 p-6 shadow-sm backdrop-blur dark:border-blue-900/70 dark:bg-blue-950/50">
                <h2 class="text-2xl font-semibold text-blue-900 dark:text-blue-100">6. Contact</h2>
                <p class="text-neutral-700 dark:text-neutral-200">
                    Pour toute question concernant cette politique ou vos données personnelles :
                </p>
                <p class="font-semibold text-blue-900 dark:text-blue-100">📧 baptiste@gmail.com</p>
            </section>
        </div>
    </AppHeaderLayout>
</template>
