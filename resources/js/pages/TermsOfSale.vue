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
        title: 'Conditions générales de vente',
        href: route('legal.terms'),
    },
];
</script>

<template>
    <Head title="Conditions générales de vente" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">Conditions générales de vente</h1>
                <p class="text-base text-neutral-600 dark:text-neutral-300">
                    Ces conditions encadrent l'abonnement Premium LevelUp (4,99 € / mois) et les modalités de paiement, de
                    renouvellement et de résiliation via Stripe.
                </p>
            </header>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">1. Objet et champ d'application</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Les présentes conditions régissent la souscription et l'utilisation de l'abonnement Premium proposé sur le
                    site LevelUp. L'abonnement permet notamment la personnalisation du profil (alias public, couleur, bordures
                    d'avatar) et l'accès prioritaire aux nouveautés Premium.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">2. Abonnement Premium</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Formule : abonnement mensuel sans engagement à 4,99 € / mois, renouvelé automatiquement.</li>
                    <li>Fonctionnalités incluses : alias public distinctif, couleur personnalisée, bordures d'avatar exclusives,
                        accès prioritaire aux nouveautés Premium et support dédié.</li>
                    <li>Prix et contenus de l'offre susceptibles d'évoluer ; toute modification tarifaire sera communiquée
                        avant son entrée en vigueur.</li>
                </ul>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">3. Processus de commande et paiement</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>La souscription nécessite la création ou l'utilisation d'un compte LevelUp.</li>
                    <li>Les paiements sont traités via Stripe Payments Europe, Ltd., acteur certifié PCI-DSS ; LevelUp ne stocke
                        aucune donnée bancaire.</li>
                    <li>Le prélèvement est effectué à chaque échéance mensuelle sur le moyen de paiement renseigné.</li>
                    <li>Les factures et justificatifs sont accessibles via le portail de facturation Stripe.</li>
                </ul>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">4. Durée, renouvellement et résiliation</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>L'abonnement est conclu pour une durée d'un mois et se renouvelle automatiquement à chaque échéance.</li>
                    <li>Vous pouvez résilier à tout moment depuis le portail de facturation Stripe disponible dans votre espace
                        <Link :href="route('billing.portal')" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">gestion d'abonnement</Link>.
                    </li>
                    <li>La résiliation prend effet à la fin de la période en cours ; les avantages Premium restent actifs jusqu'à
                        cette date.</li>
                </ul>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">5. Droit de rétractation</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    L'abonnement Premium est un service numérique exécuté immédiatement après la validation du paiement. En
                    souscrivant, vous consentez à l'exécution immédiate et renoncez expressément à votre droit de rétractation.
                    Vous pouvez néanmoins résilier à tout moment pour les périodes futures via le portail de facturation.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">6. Prix, facturation et évolutions</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Le prix en vigueur est affiché lors de la souscription. Toute évolution tarifaire sera notifiée préalablement.</li>
                    <li>Les factures sont émises par Stripe et disponibles depuis votre espace de facturation.</li>
                    <li>En cas d'impayé ou d'échec de prélèvement, l'accès aux avantages Premium pourra être suspendu jusqu'à
                        régularisation.</li>
                </ul>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">7. Support et réclamations</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Pour toute question ou réclamation relative à votre abonnement, contactez
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">baptiste@gmail.com</span>. Une réponse vous
                    sera apportée dans les meilleurs délais.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">8. Responsabilité et disponibilité du service</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    LevelUp met tout en œuvre pour assurer un service disponible et sécurisé. La responsabilité de LevelUp ne
                    saurait être engagée en cas d'interruption temporaire, de perte de données non imputable à une faute lourde,
                    ou d'utilisation non conforme du service.
                </p>
            </section>

            <section class="space-y-3 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">9. Modification des conditions</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    LevelUp se réserve le droit d'adapter les présentes conditions. Toute modification substantielle sera notifiée
                    aux abonnés avant son application. La poursuite de l'abonnement après notification vaut acceptation des
                    nouvelles conditions.
                </p>
            </section>
        </div>
    </AppHeaderLayout>
</template>
