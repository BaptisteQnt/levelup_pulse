<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Sparkles } from 'lucide-vue-next';

type NextStep = {
    title: string;
    description: string;
    href: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: route('dashboard'),
    },
    {
        title: 'Confirmation du paiement',
        href: route('billing.success'),
    },
];

const nextSteps: NextStep[] = [
    {
        title: 'Configurer votre profil Premium',
        description: 'Choisissez votre couleur, bordure et alias pour refléter votre style dès maintenant.',
        href: route('settings.premium'),
    },
    {
        title: 'Explorer l’espace Premium',
        description: 'Retrouvez tous vos avantages réunis dans une interface dédiée aux membres Premium.',
        href: '/premium',
    },
    {
        title: 'Retourner au tableau de bord',
        description: 'Consultez vos statistiques, vos activités récentes et les dernières nouveautés.',
        href: route('dashboard'),
    },
];
</script>

<template>
    <Head title="Paiement confirmé" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-10 px-4 py-16">
            <Card class="overflow-hidden border border-primary/20 bg-white/70 shadow-lg backdrop-blur dark:border-primary/20 dark:bg-neutral-900/70">
                <CardHeader class="flex flex-col items-center gap-4 border-b border-primary/10 bg-gradient-to-br from-primary/20 via-primary/10 to-primary/30 p-10 text-center dark:border-primary/20 dark:from-primary/30 dark:via-primary/20 dark:to-primary/40">
                    <span class="flex size-16 items-center justify-center rounded-full bg-white text-primary shadow-lg dark:bg-neutral-950">
                        <CheckCircle2 class="size-8" />
                    </span>
                    <div class="space-y-2">
                        <CardTitle class="text-3xl font-semibold text-neutral-900 dark:text-neutral-50">Paiement confirmé !</CardTitle>
                        <CardDescription class="text-base text-neutral-600 dark:text-neutral-300">
                            Merci d’avoir rejoint les membres Premium. Votre abonnement est maintenant actif et vos avantages seront appliqués en quelques instants.
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent class="space-y-6 p-8">
                    <div class="rounded-2xl border border-primary/20 bg-primary/5 p-6 text-sm text-neutral-700 shadow-inner dark:border-primary/30 dark:bg-primary/10 dark:text-neutral-200">
                        <div class="flex items-start gap-3">
                            <Sparkles class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary" />
                            <p>
                                Profitez immédiatement des fonctionnalités avancées&nbsp;: apparence personnalisée, mises à jour prioritaires et accès simplifié à votre gestion de facturation.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Et maintenant&nbsp;?</h2>
                        <ul class="grid gap-4 md:grid-cols-2">
                            <li
                                v-for="step in nextSteps"
                                :key="step.href"
                                class="rounded-2xl border border-primary/10 bg-white/80 p-4 text-sm shadow-sm transition hover:border-primary/40 hover:shadow-md dark:border-primary/20 dark:bg-neutral-900/60"
                            >
                                <h3 class="text-base font-semibold text-neutral-900 dark:text-neutral-50">{{ step.title }}</h3>
                                <p class="mt-1 text-neutral-600 dark:text-neutral-300">{{ step.description }}</p>
                                <Button as-child variant="link" class="mt-3 px-0 text-sm font-semibold text-primary">
                                    <Link :href="step.href">Découvrir</Link>
                                </Button>
                            </li>
                        </ul>
                    </div>
                </CardContent>
                <CardFooter class="flex flex-col gap-3 border-t border-primary/10 bg-primary/5/50 p-8 text-sm dark:border-primary/20 dark:bg-primary/10">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Button as-child size="lg" class="min-w-[200px] justify-center text-base font-semibold">
                            <Link :href="route('dashboard')">Aller au tableau de bord</Link>
                        </Button>
                        <Button as-child variant="outline" size="lg" class="min-w-[200px] justify-center text-base font-semibold">
                            <Link :href="route('billing.portal')">Gérer mon abonnement</Link>
                        </Button>
                    </div>
                    <p class="text-center text-xs text-neutral-500 dark:text-neutral-400">
                        Un e-mail de confirmation vous a été envoyé. Vous pourrez y retrouver votre facture et toutes les informations liées à votre abonnement.
                    </p>
                </CardFooter>
            </Card>
        </div>
    </AppHeaderLayout>
</template>
