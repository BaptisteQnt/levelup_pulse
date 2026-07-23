<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type DataRequest = {
    id: number;
    request_type: 'account_deletion' | 'data_deletion';
    details: string | null;
    status: 'pending' | 'in_progress' | 'resolved';
    admin_notes: string | null;
    created_at: string | null;
    resolved_at: string | null;
};

type FlashMessage = {
    success?: string | null;
    error?: string | null;
};

const props = defineProps<{
    requests: DataRequest[];
    flash?: FlashMessage;
}>();

const page = usePage<{ auth: { user: unknown }; flash?: FlashMessage }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Politique de confidentialité',
        href: route('legal.privacy'),
    },
];

const form = useForm({
    request_type: 'account_deletion' as 'account_deletion' | 'data_deletion',
    details: '',
});

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const flash = computed<FlashMessage | undefined>(() => props.flash ?? page.props.flash);

const submit = () => {
    form.post(route('legal.requests.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('details');
        },
    });
};

const requestTypeLabels: Record<DataRequest['request_type'], string> = {
    account_deletion: 'Suppression du compte',
    data_deletion: 'Suppression des données personnelles',
};

const statusLabels: Record<DataRequest['status'], string> = {
    pending: 'En attente',
    in_progress: 'En cours de traitement',
    resolved: 'Résolue',
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '—';
</script>

<template>
    <Head title="Politique de confidentialité" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex max-w-5xl flex-col gap-8 px-4 py-10">
            <header class="space-y-3">
                <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">Politique de confidentialité</h1>
                <p class="text-base text-neutral-600 dark:text-neutral-300">
                    Découvrez comment LevelUp traite vos données personnelles, pour quelles finalités et comment exercer vos
                    droits à tout moment.
                </p>
            </header>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">1. Responsable du traitement</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Le site LevelUp est édité par Baptiste (Chambly, France), joignable à l'adresse
                    <span class="font-semibold text-neutral-900 dark:text-neutral-100">baptiste@gmail.com</span>.
                    Les traitements sont opérés dans le cadre d'un service communautaire et d'un abonnement Premium.
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">2. Données collectées</h2>
                <p class="text-neutral-600 dark:text-neutral-300">Les catégories de données susceptibles d'être collectées incluent :</p>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Identité et contact : nom, prénom, adresse e-mail, numéro de téléphone, pays de résidence.</li>
                    <li>Compte : identifiants, mot de passe haché, pseudo public, préférences de profil.</li>
                    <li>Abonnement et paiement : historique d'abonnement Premium, transactions gérées par Stripe (aucune donnée bancaire n'est stockée sur LevelUp).</li>
                    <li>Navigation et audience : adresse IP, traces techniques, cookies nécessaires, mesures d'audience (Google Analytics, Microsoft Clarity) selon vos choix.</li>
                    <li>Contenus et interactions : notes, reactions aux articles et contributions publiees par les redacteurs.</li>
                    <li>Test de compatibilité PC : version de Windows et DirectX, processeur, carte graphique et pilote, mémoire vive, type et espace des disques. Aucun nom de machine, numéro de série, fichier ou logiciel installé n'est collecté.</li>
                </ul>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">3. Finalités et bases légales</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg bg-neutral-50 p-4 text-neutral-700 shadow-sm dark:bg-neutral-800 dark:text-neutral-200">
                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Gestion du service</h3>
                        <ul class="mt-2 list-disc space-y-2 pl-4">
                            <li>Création et gestion des comptes utilisateurs.</li>
                            <li>Accès aux fonctionnalités Premium et personnalisation du profil.</li>
                            <li>Gestion editoriale des articles et prevention des abus.</li>
                            <li>Estimation ponctuelle de la compatibilité d'un PC avec le jeu sélectionné, après consentement explicite.</li>
                        </ul>
                        <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">Base légale : exécution du contrat et intérêt légitime de sécuriser la plateforme.</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4 text-neutral-700 shadow-sm dark:bg-neutral-800 dark:text-neutral-200">
                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Facturation & support</h3>
                        <ul class="mt-2 list-disc space-y-2 pl-4">
                            <li>Gestion des paiements par Stripe et suivi des renouvellements.</li>
                            <li>Emission de factures et suivi des demandes liées à l'abonnement.</li>
                            <li>Support client et traitement des réclamations.</li>
                        </ul>
                        <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">Base légale : exécution du contrat et obligations légales liées à la facturation.</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4 text-neutral-700 shadow-sm dark:bg-neutral-800 dark:text-neutral-200">
                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Mesure d'audience</h3>
                        <ul class="mt-2 list-disc space-y-2 pl-4">
                            <li>Analyse du trafic et amélioration de l'expérience utilisateur.</li>
                            <li>Collecte de statistiques via Google Analytics et Microsoft Clarity.</li>
                        </ul>
                        <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">Base légale : consentement pour les traceurs non essentiels.</p>
                    </div>
                    <div class="rounded-lg bg-neutral-50 p-4 text-neutral-700 shadow-sm dark:bg-neutral-800 dark:text-neutral-200">
                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Conformité & sécurité</h3>
                        <ul class="mt-2 list-disc space-y-2 pl-4">
                            <li>Prévention des abus et fraude.</li>
                            <li>Sécurisation des accès et journalisation technique.</li>
                            <li>Réponse aux demandes légales ou réglementaires.</li>
                        </ul>
                        <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">Base légale : obligation légale et intérêt légitime.</p>
                    </div>
                </div>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">4. Durées de conservation</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Données de compte : conservées tant que le compte est actif, puis supprimées ou anonymisées sur demande.</li>
                    <li>Données de facturation : conservées conformément aux obligations comptables et fiscales applicables.</li>
                    <li>Cookies analytiques : durée maximale de 13 mois selon vos préférences de consentement.</li>
                    <li>Contenus publies : conserves tant qu'ils restent en ligne ou jusqu'a suppression editoriale.</li>
                    <li>Tests de compatibilité PC : données matérielles et résultat supprimés automatiquement sous 24 heures, sans création de profil permanent.</li>
                </ul>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">5. Destinataires et sous-traitants</h2>
                <ul class="list-disc space-y-2 pl-5 text-neutral-600 dark:text-neutral-300">
                    <li>Stripe Payments Europe Ltd. pour le traitement sécurisé des paiements (LevelUp ne stocke pas vos données bancaires).</li>
                    <li>Outils d'analyse (Google Analytics, Microsoft Clarity) activés uniquement après consentement.</li>
                    <li>Hébergeur Hostinger International Ltd. pour la conservation des données applicatives.</li>
                    <li>Equipe editoriale LevelUp, dans le cadre de la lutte contre les abus et de la conformite du media.</li>
                    <li>OpenAI, uniquement pendant un test demandé par l'utilisateur, pour rechercher les prérequis du jeu et produire l'estimation de compatibilité.</li>
                </ul>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">6. Vos droits</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Vous pouvez exercer à tout moment vos droits d'accès, de rectification, d'effacement, de limitation, d'opposition,
                    de portabilité ainsi que le retrait de votre consentement pour les cookies non essentiels.
                </p>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Contact dédié : <span class="font-semibold text-neutral-900 dark:text-neutral-100">baptiste@gmail.com</span> ou via le formulaire ci-dessous pour les demandes liées aux données.
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">7. Cookies et mesure d'audience</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Les cookies nécessaires assurent le bon fonctionnement du site. Les cookies analytiques et de mesure d'audience sont déposés uniquement après votre accord via TarteAuCitron.
                    Vous pouvez modifier vos choix à tout moment.
                </p>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Pour plus d'informations, consultez notre
                    <Link :href="route('legal.cookies')" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">politique des cookies</Link>.
                </p>
            </section>

            <section class="space-y-4 rounded-xl border border-sidebar-border/70 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-sidebar-border dark:bg-neutral-900/80">
                <h2 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">8. Sécurité</h2>
                <p class="text-neutral-600 dark:text-neutral-300">
                    Les données sont protégées par des mesures techniques et organisationnelles adaptées : chiffrement des communications,
                    contrôle des accès, journalisation des actions sensibles et hébergement sécurisé.
                </p>
            </section>

            <section class="space-y-6 rounded-xl border border-blue-200/70 bg-blue-50/80 p-6 shadow-sm backdrop-blur dark:border-blue-900/70 dark:bg-blue-950/50">
                <div class="space-y-2">
                    <h2 class="text-2xl font-semibold text-blue-900 dark:text-blue-100">Exercer vos droits</h2>
                    <p class="text-neutral-700 dark:text-neutral-200">
                        Utilisez le formulaire pour demander la suppression de votre compte ou de vos données personnelles. Notre équipe vous
                        répondra dans les meilleurs délais.
                    </p>
                </div>

                <div v-if="flash?.success" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">
                    {{ flash.success }}
                </div>
                <div v-if="flash?.error" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-700 dark:bg-red-900/40 dark:text-red-200">
                    {{ flash.error }}
                </div>

                <div v-if="isAuthenticated" class="space-y-6">
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="space-y-2">
                            <label for="request_type" class="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                Type de demande
                            </label>
                            <select
                                id="request_type"
                                v-model="form.request_type"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-800 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
                            >
                                <option value="account_deletion">Suppression du compte</option>
                                <option value="data_deletion">Suppression des données personnelles</option>
                            </select>
                            <p v-if="form.errors.request_type" class="text-sm text-red-500">{{ form.errors.request_type }}</p>
                        </div>

                        <div class="space-y-2">
                            <label for="details" class="block text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                Précisions complémentaires (facultatif)
                            </label>
                            <textarea
                                id="details"
                                v-model="form.details"
                                rows="4"
                                class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-800 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100"
                                placeholder="Expliquez votre demande pour nous aider à la traiter rapidement."
                            ></textarea>
                            <p v-if="form.errors.details" class="text-sm text-red-500">{{ form.errors.details }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-500 dark:hover:bg-blue-400"
                        >
                            {{ form.processing ? 'Envoi en cours…' : 'Envoyer ma demande' }}
                        </button>
                    </form>

                    <section class="space-y-4">
                        <h3 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Historique de vos demandes</h3>
                        <p v-if="props.requests.length === 0" class="text-sm text-neutral-600 dark:text-neutral-300">
                            Vous n'avez pas encore soumis de demande de suppression.
                        </p>
                        <div v-else class="space-y-4">
                            <article
                                v-for="request in props.requests"
                                :key="request.id"
                                class="rounded-lg border border-neutral-200 bg-white px-4 py-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900"
                            >
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <h4 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                        {{ requestTypeLabels[request.request_type] }}
                                    </h4>
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="{
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200': request.status === 'pending',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200': request.status === 'in_progress',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200': request.status === 'resolved',
                                        }"
                                    >
                                        {{ statusLabels[request.status] }}
                                    </span>
                                </div>
                                <p v-if="request.details" class="mt-2 text-sm text-neutral-700 dark:text-neutral-200">{{ request.details }}</p>
                                <dl class="mt-3 grid gap-2 text-xs text-neutral-500 dark:text-neutral-400 sm:grid-cols-2">
                                    <div>
                                        <dt class="font-semibold uppercase tracking-wide">Créée le</dt>
                                        <dd>{{ formatDate(request.created_at) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold uppercase tracking-wide">Résolue le</dt>
                                        <dd>{{ formatDate(request.resolved_at) }}</dd>
                                    </div>
                                    <div v-if="request.admin_notes" class="sm:col-span-2">
                                        <dt class="font-semibold uppercase tracking-wide">Message de l'équipe</dt>
                                        <dd class="text-neutral-700 dark:text-neutral-200">{{ request.admin_notes }}</dd>
                                    </div>
                                </dl>
                            </article>
                        </div>
                    </section>
                </div>
                <div v-else class="rounded-lg border border-neutral-200 bg-white/70 px-4 py-5 text-sm text-neutral-700 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70 dark:text-neutral-200">
                    Connectez-vous pour accéder au formulaire et suivre vos demandes de suppression.
                </div>
            </section>
        </div>
    </AppHeaderLayout>
</template>
