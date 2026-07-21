<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Mentions légales',
        href: route('legal.mentions'),
    },
];
</script>

<template>
    <Head title="Mentions légales" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">Mentions légales</h1>
                <p class="text-base text-neutral-600 dark:text-neutral-300">
                    Retrouvez ici les informations juridiques relatives à LevelUp. Pour en savoir plus sur la gestion de vos
                    données personnelles et l'exercice de vos droits, consultez la
                    <Link :href="route('legal.privacy')" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                        politique de confidentialité
                    </Link>.
                </p>
            </header>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Éditeur du site</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Le présent site est édité par Baptiste (particulier, étudiant), domicilié à Chambly, France.
                    <br />
                    Contact : <span class="font-semibold text-neutral-900 dark:text-neutral-100">baptiste@gmail.com</span>
                    <br />
                    Directeur de la publication : Baptiste.
                    <br />
                    Le site est édité dans un cadre non professionnel et ne nécessite pas de numéro SIRET.
                </p>
                <div class="grid gap-2 text-neutral-600 dark:text-neutral-300 sm:grid-cols-2">
                    <p>
                        Le site est hébergé par : Hostinger International Ltd. Adresse : Švitrigailos g. 34, LT-03230 Vilnius,
                        Lituanie. Téléphone : +370 645 03378. Site web : https://www.hostinger.com
                    </p>
                </div>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Cookies</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Le site utilise un gestionnaire de consentement (TarteAuCitron) et des traceurs nécessaires au bon
                    fonctionnement. Pour les informations détaillées et la gestion de vos préférences, consultez la
                    <Link :href="route('legal.cookies')" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                        politique des cookies
                    </Link>.
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Contenu editorial</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Les articles sont rediges par les redacteurs du media. Les utilisateurs peuvent consulter les articles,
                    noter les jeux et reagir aux publications.<br />
                    L'editeur se reserve le droit de modifier ou supprimer tout contenu contraire a la loi ou a la ligne editoriale.
                </p>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Responsabilité :<br />
                    L’éditeur ne peut être tenu responsable des contenus publiés par les utilisateurs, des problèmes techniques
                    liés à l’hébergement, ou des liens vers des sites externes dont les contenus n’engagent que leurs auteurs. Le
                    site contient des informations et avis communautaires qui ne sauraient remplacer un conseil professionnel.
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Abonnement et paiement</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Les paiements sont gérés par Stripe Payments Europe, Ltd. Les données bancaires ne sont pas stockées sur
                    LevelUp et sont traitées directement par Stripe, conforme PCI-DSS.
                </p>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Retrouvez les conditions applicables à l'abonnement Premium dans les
                    <Link :href="route('legal.terms')" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">
                        conditions générales de vente
                    </Link>.
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Propriété intellectuelle</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Les éléments du site (textes, logo, visuels, structure) sont protégés. Toute reproduction totale ou
                    partielle est interdite sans autorisation.
                    <br />
                    Les contenus publiés par les utilisateurs restent leur propriété mais peuvent être réutilisés par le site
                    dans le cadre de son fonctionnement (affichage public, classement, etc.).
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Contact</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Pour toute question concernant le site ou son contenu, vous pouvez écrire à
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">baptiste@gmail.com</span>.
                </p>
            </section>
        </div>
    </AppHeaderLayout>
</template>
