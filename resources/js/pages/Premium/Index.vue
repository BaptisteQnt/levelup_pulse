<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { useInitials } from '@/composables/useInitials';
import { isPremiumUser, resolveAlias, resolveBorderClass, resolveNameColor } from '@/lib/premium';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Crown, IdCard, Palette, Sparkles } from 'lucide-vue-next';
import { computed, type Component } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Espace Premium',
        href: '/premium',
    },
];

const page = usePage<SharedData>();

const currentUser = computed(() => page.props.auth.user as User | null);
const isSubscribed = computed(() => isPremiumUser(currentUser.value));
const displayNameColor = computed(() => resolveNameColor(currentUser.value));
const displayAlias = computed(() => resolveAlias(currentUser.value));
const avatarBorderClass = computed(() => resolveBorderClass(currentUser.value?.profile_border_style));

const { getInitials } = useInitials();

interface HeroAction {
    label: string;
    href: string;
    variant: 'default' | 'outline';
}

const heroActions = computed<HeroAction[]>(() => {
    if (!isSubscribed.value) {
        return [
            {
                label: 'Découvrir les offres',
                href: route('billing.plans'),
                variant: 'default' as const,
            },
            {
                label: 'Retour au tableau de bord',
                href: route('dashboard'),
                variant: 'outline' as const,
            },
        ];
    }

    return [
        {
            label: 'Personnaliser mon profil',
            href: route('settings.premium'),
            variant: 'default' as const,
        },
        {
            label: 'Gérer mon abonnement',
            href: route('billing.portal'),
            variant: 'outline' as const,
        },
    ];
});

interface Feature {
    title: string;
    description: string;
    icon: Component;
}

const features = computed<Feature[]>(() => [
    {
        title: 'Identité visuelle sur mesure',
        description:
            'Choisissez la couleur qui représente le mieux votre style et affichez-la instantanément dans toutes vos interactions.',
        icon: Palette,
    },
    {
        title: 'Alias public distinctif',
        description:
            "Ajoutez un pseudo affiché pour être reconnu en un clin d'œil par la communauté et renforcer votre présence.",
        icon: IdCard,
    },
    {
        title: 'Avatar mis en valeur',
        description:
            'Activez des bordures lumineuses exclusives pour votre avatar et démarquez-vous dans les commentaires et classements.',
        icon: Sparkles,
    },
    {
        title: 'Accès prioritaire',
        description:
            'Des raccourcis dédiés vous permettent de modifier vos préférences premium et de gérer votre abonnement en un instant.',
        icon: Crown,
    },
]);

interface Step {
    title: string;
    description: string;
    actionLabel: string;
    href: string;
}

const steps = computed<Step[]>(() => [
    {
        title: '1. Compléter votre profil',
        description:
            'Ajoutez vos informations principales (nom, pseudo, coordonnées) pour activer toutes les options Premium.',
        actionLabel: 'Mettre à jour mon profil',
        href: route('profile.edit'),
    },
    {
        title: '2. Ouvrir les réglages Premium',
        description:
            "Sélectionnez votre couleur préférée, définissez un alias et choisissez une bordure animée pour votre avatar.",
        actionLabel: 'Accéder aux réglages Premium',
        href: route('settings.premium'),
    },
    {
        title: '3. Gérer la facturation',
        description:
            "Accédez au portail Stripe pour mettre à jour votre formule, vos moyens de paiement ou consulter vos factures.",
        actionLabel: 'Ouvrir le portail de facturation',
        href: route('billing.portal'),
    },
]);

const memberSince = computed(() => {
    if (!currentUser.value?.created_at) {
        return null;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(currentUser.value.created_at));
});
</script>

<template>
    <Head title="Espace Premium" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-4 py-10">
            <section
                class="overflow-hidden rounded-3xl border border-primary/20 bg-gradient-to-br from-primary/10 via-white to-primary/5 p-8 shadow-sm dark:from-primary/20 dark:via-neutral-950 dark:to-neutral-900"
            >
                <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl space-y-4">
                        <span class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-white/60 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary shadow-sm dark:bg-neutral-900/70">
                            <Crown class="h-4 w-4" />
                            Premium
                        </span>
                        <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-50">Bienvenue dans votre espace Premium</h1>
                        <p class="text-base leading-relaxed text-neutral-600 dark:text-neutral-300">
                            Retrouvez ici toutes les options pour personnaliser votre expérience LevelUp. Ajustez votre profil, explorez les avantages exclusifs et gérez votre abonnement en quelques clics.
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <Button
                                v-for="action in heroActions"
                                :key="action.href"
                                :variant="action.variant"
                                as-child
                            >
                                <Link :href="action.href">{{ action.label }}</Link>
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="currentUser"
                        class="relative flex w-full max-w-sm flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-white/70 p-6 text-sm shadow-lg backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/70"
                    >
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-neutral-100 text-lg font-semibold text-neutral-600 shadow-sm dark:bg-neutral-800 dark:text-neutral-200"
                                :class="isSubscribed ? avatarBorderClass : ''"
                            >
                                <img
                                    v-if="currentUser.avatar"
                                    :src="currentUser.avatar"
                                    :alt="`Avatar de ${currentUser.name}`"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ getInitials(currentUser.name) }}</span>
                            </div>
                            <div class="flex flex-1 flex-col">
                                <span
                                    class="text-base font-semibold text-neutral-900 dark:text-neutral-100"
                                    :style="displayNameColor ? { color: displayNameColor } : undefined"
                                >
                                    {{ currentUser.name }}
                                </span>
                                <span
                                    v-if="displayAlias"
                                    class="text-xs font-medium uppercase tracking-wide text-primary/80"
                                >
                                    {{ displayAlias }}
                                </span>
                                <span
                                    v-if="memberSince"
                                    class="text-xs text-neutral-500 dark:text-neutral-400"
                                >
                                    Membre depuis {{ memberSince }}
                                </span>
                            </div>
                        </div>
                        <p class="text-xs leading-relaxed text-neutral-600 dark:text-neutral-400">
                            Astuce : retrouvez toutes les options de personnalisation dans les paramètres Premium. Chaque modification est sauvegardée instantanément et visible par toute la communauté.
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <Card v-for="feature in features" :key="feature.title" class="h-full border-sidebar-border/60 bg-white/80 backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/70">
                    <CardHeader class="flex flex-row items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary dark:bg-primary/20">
                            <component :is="feature.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                {{ feature.title }}
                            </CardTitle>
                            <CardDescription class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                {{ feature.description }}
                            </CardDescription>
                        </div>
                    </CardHeader>
                </Card>
            </section>

            <section class="space-y-6 rounded-2xl border border-sidebar-border/70 bg-white/80 p-8 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/70">
                <div class="space-y-2">
                    <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-50">Comment personnaliser votre profil</h2>
                    <p class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                        Suivez ces étapes pour profiter pleinement de votre abonnement Premium et rendre votre présence sur LevelUp inoubliable.
                    </p>
                </div>
                <div class="grid gap-6 md:grid-cols-3">
                    <Card
                        v-for="step in steps"
                        :key="step.title"
                        class="h-full border-transparent bg-white/90 shadow-none ring-1 ring-sidebar-border/60 dark:bg-neutral-900/90 dark:ring-sidebar-border"
                    >
                        <CardHeader class="space-y-3">
                            <CardTitle class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                {{ step.title }}
                            </CardTitle>
                            <CardDescription class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-400">
                                {{ step.description }}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button as-child class="w-full">
                                <Link :href="step.href">{{ step.actionLabel }}</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </div>
    </AppHeaderLayout>
</template>
