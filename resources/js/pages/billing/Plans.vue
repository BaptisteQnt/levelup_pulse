<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Crown, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

type StripePrice = {
    id?: string | null;
    name: string;
    amount: string;
    description?: string | null;
};

const props = defineProps<{
    stripeKey?: string | null;
    prices: StripePrice[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: route('dashboard'),
    },
    {
        title: 'Abonnement Premium',
        href: route('billing.plans'),
    },
];

const availablePrices = computed(() => props.prices.filter((price) => Boolean(price.id)));

const configurationMissing = computed(() => availablePrices.value.length === 0);

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const isProfileComplete = computed(() => Boolean(user.value?.is_profile_complete));

const includedFeatures = [
    "Alias public et couleur personnalisée pour vous distinguer dans la communauté.",
    'Avatar mis en valeur avec des bordures exclusives et animations lumineuses.',
    'Accès prioritaire aux nouveautés Premium et à leur feuille de route.',
    'Support privilégié pour vos questions liées à votre abonnement.',
];
</script>

<template>
    <Head title="Abonnement Premium" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-5xl flex-col gap-10 px-4 py-10">
            <section
                class="overflow-hidden rounded-3xl border border-primary/20 bg-gradient-to-br from-primary/10 via-white to-primary/5 p-8 shadow-sm dark:from-primary/20 dark:via-neutral-950 dark:to-neutral-900"
            >
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-xl space-y-4">
                        <span
                            class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-white/70 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-primary shadow-sm dark:bg-neutral-900/70"
                        >
                            <Crown class="h-4 w-4" />
                            Premium LevelUp
                        </span>
                        <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-50">
                            Passez au niveau supérieur avec Premium
                        </h1>
                        <p class="text-base leading-relaxed text-neutral-600 dark:text-neutral-300">
                            Personnalisez votre identité, mettez votre profil en lumière et profitez d’un accompagnement dédié. Choisissez la formule qui correspond à vos ambitions et rejoignez les membres les plus impliqués de LevelUp.
                        </p>
                    </div>
                    <div
                        class="relative flex w-full max-w-sm flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-white/70 p-6 text-sm shadow-lg backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/70"
                    >
                        <div class="flex items-center gap-3 text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            <ShieldCheck class="h-5 w-5 text-primary" />
                            Paiement sécurisé par Stripe
                        </div>
                        <p class="text-sm leading-relaxed text-neutral-600 dark:text-neutral-300">
                            Nous utilisons Stripe pour traiter les paiements. Toutes les transactions sont chiffrées et vous pouvez résilier votre abonnement à tout moment depuis votre espace de facturation.
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="!isProfileComplete"
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-900 shadow-sm dark:border-amber-300/40 dark:bg-amber-900/30 dark:text-amber-50"
            >
                <ShieldCheck class="mt-0.5 h-5 w-5 text-amber-600 dark:text-amber-200" />
                <div class="space-y-1">
                    <p class="font-semibold">Complétez votre profil pour continuer</p>
                    <p class="text-amber-800 dark:text-amber-100">
                        Nous avons besoin de vos informations de profil avant de lancer le paiement. Renseignez vos coordonnées pour accéder à l’abonnement Premium.
                        <Link
                            :href="route('profile.edit')"
                            class="ml-1 font-semibold text-amber-900 underline underline-offset-4 hover:text-amber-700 dark:text-amber-50 dark:hover:text-white"
                        >
                            Compléter mon profil
                        </Link>
                    </p>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <Card
                    v-for="price in props.prices"
                    :key="price.name"
                    class="relative flex h-full flex-col justify-between overflow-hidden border border-primary/30 bg-white/80 shadow-sm backdrop-blur dark:border-primary/20 dark:bg-neutral-900/60"
                >
                    <CardHeader class="space-y-2 border-b border-primary/10 bg-primary/5/50 p-6 dark:border-primary/20 dark:bg-primary/10">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-xl font-semibold text-neutral-900 dark:text-neutral-50">
                                {{ price.name }}
                            </CardTitle>
                            <span class="rounded-full bg-primary/15 px-3 py-1 text-xs font-medium text-primary dark:bg-primary/20">
                                Sans engagement
                            </span>
                        </div>
                        <CardDescription class="text-base text-neutral-600 dark:text-neutral-300">
                            {{ price.description || 'Profitez de toutes les options de personnalisation et de nos futures nouveautés.' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col gap-6 p-6">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                Tarification
                            </p>
                            <p class="mt-2 text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ price.amount }}</p>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Renouvellement automatique, annulation possible en un clic.</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                Ce qui est inclus
                            </p>
                            <ul class="mt-3 space-y-3">
                                <li
                                    v-for="feature in includedFeatures"
                                    :key="feature"
                                    class="flex items-start gap-3 text-sm text-neutral-600 dark:text-neutral-300"
                                >
                                    <CheckCircle2 class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary" />
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                    <CardFooter class="flex flex-col gap-3 border-t border-primary/10 bg-primary/5/50 p-6 dark:border-primary/20 dark:bg-primary/10">
                        <Button
                            v-if="price.id && isProfileComplete"
                            as-child
                            size="lg"
                            class="w-full justify-center text-base font-semibold"
                        >
                            <Link :href="route('billing.checkout', { price: price.id })">
                                S’abonner maintenant
                            </Link>
                        </Button>
                        <Button
                            v-else-if="price.id"
                            as-child
                            variant="outline"
                            size="lg"
                            class="w-full justify-center text-base font-semibold"
                        >
                            <Link :href="route('profile.edit')">Compléter mon profil</Link>
                        </Button>
                        <Button v-else disabled size="lg" class="w-full justify-center text-base font-semibold">
                            Configuration requise
                        </Button>
                        <p v-if="price.id && !isProfileComplete" class="text-center text-xs text-neutral-600 dark:text-neutral-300">
                            Votre profil doit être complété avant de souscrire. Mettez-le à jour dans vos paramètres.
                        </p>
                        <p v-else-if="!price.id" class="text-center text-xs text-destructive">
                            Aucun identifiant Stripe valide n’a été détecté pour cette offre. Veuillez contacter l’équipe technique.
                        </p>
                    </CardFooter>
                </Card>

                <Card class="h-full border border-primary/10 bg-white/60 shadow-sm backdrop-blur dark:border-primary/20 dark:bg-neutral-900/60">
                    <CardHeader class="space-y-2 p-6">
                        <CardTitle class="text-xl font-semibold text-neutral-900 dark:text-neutral-50">
                            Déjà membre Premium ?
                        </CardTitle>
                        <CardDescription class="text-sm text-neutral-600 dark:text-neutral-300">
                            Accédez à votre espace personnalisé pour ajuster vos préférences ou gérer la facturation en temps réel.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <div class="grid gap-3">
                            <Button as-child variant="outline" size="lg" class="justify-start text-sm font-medium">
                                <Link href="/premium">Découvrir l’espace Premium</Link>
                            </Button>
                            <Button as-child variant="ghost" size="lg" class="justify-start text-sm font-medium">
                                <Link :href="route('billing.portal')">Gérer mon abonnement</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section
                v-if="configurationMissing"
                class="rounded-2xl border border-destructive/40 bg-destructive/10 p-6 text-sm text-destructive shadow-inner dark:border-destructive/30 dark:bg-destructive/15"
            >
                Aucun prix Stripe n’est actuellement configuré. Ajoutez l’identifiant dans vos variables d’environnement ou contactez un administrateur pour finaliser la mise en place de l’abonnement Premium.
            </section>
        </div>
    </AppHeaderLayout>
</template>
