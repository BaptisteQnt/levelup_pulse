<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type ScanStatus = 'created' | 'uploaded' | 'researching' | 'analyzing' | 'completed' | 'failed' | 'expired';
type Verdict = 'high' | 'medium' | 'low' | 'incompatible' | 'unknown';
type CheckStatus = 'pass' | 'warn' | 'fail' | 'unknown';

type ComponentCheck = {
    component: 'cpu' | 'gpu' | 'ram' | 'vram' | 'storage' | 'ssd' | 'os' | 'directx';
    status: CheckStatus;
    observed: string;
    requirement: string;
    explanation: string;
};

type CompatibilityResult = {
    verdict: Verdict;
    summary: string;
    component_checks: ComponentCheck[];
    bottlenecks: string[];
    advice: string[];
    disclaimer: string;
};

type Source = {
    title: string;
    url: string;
    publisher: string | null;
};

type ScanResponse = {
    id: string;
    status: ScanStatus;
    result: CompatibilityResult | null;
    sources: Source[];
    researched_at: string | null;
    completed_at: string | null;
    expires_at: string;
    error_code: string | null;
};

const props = defineProps<{
    gameId: number;
    gameSlug: string;
    gameTitle: string;
}>();

const consent = ref(false);
const isWindows = ref<boolean | null>(null);
const downloading = ref(false);
const scanId = ref<string | null>(null);
const scan = ref<ScanResponse | null>(null);
const scriptHash = ref<string | null>(null);
const uploadExpiresAt = ref<string | null>(null);
const errorMessage = ref<string | null>(null);
let pollTimer: ReturnType<typeof setTimeout> | null = null;

const scriptFilename = computed(() => `LevelUpPulse-${props.gameSlug}.ps1`);
const isBusy = computed(() => scan.value && ['created', 'uploaded', 'researching', 'analyzing'].includes(scan.value.status));

const statusText = computed(() => {
    switch (scan.value?.status) {
        case 'created':
            return 'Script prêt : exécutez-le avant l’expiration du jeton.';
        case 'uploaded':
            return 'Configuration reçue, préparation de la recherche…';
        case 'researching':
            return 'Recherche des prérequis officiels du jeu…';
        case 'analyzing':
            return 'Comparaison de votre PC avec les prérequis…';
        case 'completed':
            return 'Analyse terminée.';
        case 'failed':
            return 'L’analyse n’a pas pu aboutir.';
        case 'expired':
            return 'Ce script a expiré. Téléchargez-en un nouveau.';
        default:
            return null;
    }
});

const verdictConfig: Record<Verdict, { label: string; classes: string }> = {
    high: { label: 'Qualité haute', classes: 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' },
    medium: { label: 'Qualité moyenne', classes: 'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-200' },
    low: { label: 'Qualité faible', classes: 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-200' },
    incompatible: { label: 'Configuration insuffisante', classes: 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200' },
    unknown: { label: 'Résultat indéterminé', classes: 'border-neutral-300 bg-neutral-50 text-neutral-800 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200' },
};

const componentLabels: Record<ComponentCheck['component'], string> = {
    cpu: 'Processeur',
    gpu: 'Carte graphique',
    ram: 'Mémoire vive',
    vram: 'Mémoire vidéo',
    storage: 'Espace disque',
    ssd: 'Type de disque',
    os: 'Windows',
    directx: 'DirectX',
};

const checkClasses: Record<CheckStatus, string> = {
    pass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200',
    warn: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200',
    fail: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-200',
    unknown: 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200',
};

const checkLabels: Record<CheckStatus, string> = {
    pass: 'OK',
    warn: 'Limite',
    fail: 'Insuffisant',
    unknown: 'Inconnu',
};

const csrfToken = () => document.head.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

const apiError = async (response: Response): Promise<string> => {
    try {
        const body = (await response.json()) as { message?: string };
        return body.message ?? `Erreur HTTP ${response.status}`;
    } catch {
        return `Erreur HTTP ${response.status}`;
    }
};

const schedulePoll = () => {
    if (pollTimer) clearTimeout(pollTimer);
    pollTimer = setTimeout(pollScan, 2_500);
};

const pollScan = async () => {
    if (!scanId.value || typeof window === 'undefined') return;

    try {
        const response = await fetch(
            route('games.compatibility-scans.show', {
                game: props.gameId,
                compatibilityScan: scanId.value,
            }),
            {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) throw new Error(await apiError(response));

        scan.value = (await response.json()) as ScanResponse;

        if (['created', 'uploaded', 'researching', 'analyzing'].includes(scan.value.status)) {
            schedulePoll();
        }
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Impossible de suivre l’analyse.';
    }
};

const downloadScript = async () => {
    if (!consent.value || isWindows.value === false || typeof window === 'undefined') return;

    downloading.value = true;
    errorMessage.value = null;
    scan.value = null;
    scanId.value = null;
    scriptHash.value = null;

    try {
        const response = await fetch(route('games.compatibility-scans.store', { game: props.gameId }), {
            method: 'POST',
            headers: {
                Accept: 'application/x-powershell',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error(await apiError(response));

        const id = response.headers.get('X-Scan-ID');
        if (!id) throw new Error('Le serveur n’a pas retourné l’identifiant du test.');

        const blob = await response.blob();
        const downloadUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.download = scriptFilename.value;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(downloadUrl), 1_000);

        scanId.value = id;
        scriptHash.value = response.headers.get('X-Script-SHA256');
        uploadExpiresAt.value = response.headers.get('X-Scan-Expires-At');
        scan.value = {
            id,
            status: 'created',
            result: null,
            sources: [],
            researched_at: null,
            completed_at: null,
            expires_at: '',
            error_code: null,
        };

        const pageUrl = new URL(window.location.href);
        pageUrl.searchParams.set('scan', id);
        window.history.replaceState({}, '', pageUrl);
        schedulePoll();
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Le script n’a pas pu être téléchargé.';
    } finally {
        downloading.value = false;
    }
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
        : null;

onMounted(() => {
    isWindows.value = /Windows NT 10\.0/i.test(navigator.userAgent);

    const id = new URL(window.location.href).searchParams.get('scan');
    if (id && /^[0-9a-f-]{36}$/i.test(id)) {
        scanId.value = id;
        void pollScan();
    }
});

onBeforeUnmount(() => {
    if (pollTimer) clearTimeout(pollTimer);
});
</script>

<template>
    <section id="compatibility-scan" class="mt-10 rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm dark:border-blue-900 dark:from-blue-950/60 dark:to-[#00072d]">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl space-y-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary">Compatibilité Windows</p>
                <h2 class="text-2xl font-bold text-[#001C55] dark:text-[#A6E1FA]">Mon PC peut-il faire tourner {{ gameTitle }} ?</h2>
                <p class="text-sm leading-relaxed text-neutral-700 dark:text-neutral-200">
                    Téléchargez un script PowerShell temporaire qui lit uniquement les composants utiles, puis recevez une estimation IA pour une qualité faible, moyenne ou haute.
                </p>
                <ul class="grid gap-1 text-sm text-neutral-600 dark:text-neutral-300 sm:grid-cols-2">
                    <li>• Windows et DirectX</li>
                    <li>• Processeur et mémoire vive</li>
                    <li>• Carte graphique, VRAM et pilote</li>
                    <li>• SSD/HDD et espace disponible</li>
                </ul>
            </div>

            <div class="w-full space-y-3 lg:max-w-sm">
                <label class="flex items-start gap-3 rounded-lg border border-blue-200 bg-white/80 p-3 text-sm text-neutral-700 dark:border-blue-900 dark:bg-neutral-950/60 dark:text-neutral-200">
                    <input v-model="consent" type="checkbox" class="mt-1 h-4 w-4 rounded border-neutral-300 text-primary focus:ring-primary" />
                    <span>
                        J’accepte l’envoi temporaire de ces caractéristiques à LevelUp Pulse et OpenAI pour ce test. LevelUp Pulse les supprimera sous 24 h ; OpenAI les traite selon ses propres conditions de conservation.
                        <Link :href="route('legal.privacy')" class="font-semibold text-primary hover:underline">En savoir plus</Link>.
                    </span>
                </label>
                <button
                    type="button"
                    :disabled="!consent || downloading || isWindows === false"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-primary px-5 py-3 text-sm font-semibold text-white shadow transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    @click="downloadScript"
                >
                    {{ downloading ? 'Préparation du script…' : 'Tester mon PC pour ce jeu' }}
                </button>
                <p v-if="isWindows === false" class="text-sm font-medium text-amber-700 dark:text-amber-300">
                    Cette fonctionnalité est disponible uniquement sur Windows 10/11 x64.
                </p>
            </div>
        </div>

        <div v-if="scanId" class="mt-6 space-y-4 rounded-lg border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950/70">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-neutral-900 dark:text-neutral-100">Test en cours</h3>
                    <p class="text-sm text-neutral-600 dark:text-neutral-300">{{ statusText }}</p>
                </div>
                <span v-if="isBusy" class="inline-flex items-center gap-2 text-sm font-medium text-primary">
                    <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-primary"></span>
                    En attente
                </span>
            </div>

            <div v-if="scan?.status === 'created'" class="space-y-2 rounded-lg bg-neutral-50 p-4 text-sm text-neutral-700 dark:bg-neutral-900 dark:text-neutral-200">
                <p><strong>1.</strong> Ouvrez votre dossier Téléchargements.</p>
                <p><strong>2.</strong> Faites un clic droit sur <code>{{ scriptFilename }}</code>, puis « Exécuter avec PowerShell ».</p>
                <p><strong>3.</strong> Gardez cette page ouverte pendant l’analyse.</p>
                <details class="rounded border border-neutral-200 bg-white p-2 text-xs dark:border-neutral-700 dark:bg-neutral-950">
                    <summary class="cursor-pointer font-semibold">Si Windows bloque l’exécution</summary>
                    <p class="mt-2">Ouvrez PowerShell dans le dossier Téléchargements et utilisez cette commande, qui ne modifie la politique que pour ce lancement :</p>
                    <code class="mt-2 block overflow-x-auto whitespace-nowrap rounded bg-neutral-900 p-2 text-neutral-100">powershell.exe -NoProfile -ExecutionPolicy Bypass -File ".\{{ scriptFilename }}"</code>
                </details>
                <p v-if="uploadExpiresAt" class="text-xs text-neutral-500">Script utilisable jusqu’au {{ formatDate(uploadExpiresAt) }}.</p>
                <p class="text-xs text-amber-700 dark:text-amber-300">Le script est personnalisé et non signé. Exécutez uniquement le fichier téléchargé depuis cette page.</p>
                <p v-if="scriptHash" class="break-all font-mono text-[11px] text-neutral-500">SHA-256 : {{ scriptHash }}</p>
            </div>
        </div>

        <p v-if="errorMessage" class="mt-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200">
            {{ errorMessage }}
        </p>

        <article
            v-if="scan?.status === 'completed' && scan.result"
            class="mt-6 space-y-6 rounded-xl border p-5"
            :class="verdictConfig[scan.result.verdict].classes"
        >
            <header class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide">Estimation IA</p>
                <h3 class="text-2xl font-bold">{{ verdictConfig[scan.result.verdict].label }}</h3>
                <p class="leading-relaxed">{{ scan.result.summary }}</p>
            </header>

            <div v-if="scan.result.component_checks.length" class="grid gap-3 md:grid-cols-2">
                <div v-for="check in scan.result.component_checks" :key="check.component" class="rounded-lg border border-current/15 bg-white/70 p-4 text-neutral-800 dark:bg-neutral-950/60 dark:text-neutral-100">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="font-semibold">{{ componentLabels[check.component] }}</h4>
                        <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="checkClasses[check.status]">{{ checkLabels[check.status] }}</span>
                    </div>
                    <dl class="mt-3 space-y-1 text-xs">
                        <div><dt class="inline font-semibold">Votre PC :</dt> <dd class="inline">{{ check.observed || 'Non déterminé' }}</dd></div>
                        <div><dt class="inline font-semibold">Prérequis :</dt> <dd class="inline">{{ check.requirement || 'Non publié' }}</dd></div>
                    </dl>
                    <p class="mt-2 text-sm">{{ check.explanation }}</p>
                </div>
            </div>

            <div v-if="scan.result.bottlenecks.length || scan.result.advice.length" class="grid gap-4 text-sm md:grid-cols-2">
                <div v-if="scan.result.bottlenecks.length">
                    <h4 class="font-semibold">Points limitants</h4>
                    <ul class="mt-1 list-disc space-y-1 pl-5"><li v-for="item in scan.result.bottlenecks" :key="item">{{ item }}</li></ul>
                </div>
                <div v-if="scan.result.advice.length">
                    <h4 class="font-semibold">Conseils</h4>
                    <ul class="mt-1 list-disc space-y-1 pl-5"><li v-for="item in scan.result.advice" :key="item">{{ item }}</li></ul>
                </div>
            </div>

            <div v-if="scan.sources.length" class="border-t border-current/15 pt-4 text-sm">
                <h4 class="font-semibold">Sources des prérequis</h4>
                <ul class="mt-2 space-y-1">
                    <li v-for="source in scan.sources" :key="source.url">
                        <a :href="source.url" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline">{{ source.title }}</a>
                        <span v-if="source.publisher"> — {{ source.publisher }}</span>
                    </li>
                </ul>
                <p v-if="scan.researched_at" class="mt-2 text-xs opacity-80">Recherche effectuée le {{ formatDate(scan.researched_at) }}.</p>
            </div>

            <p class="border-t border-current/15 pt-4 text-xs opacity-80">{{ scan.result.disclaimer }}</p>
        </article>

        <p v-if="scan && ['failed', 'expired'].includes(scan.status)" class="mt-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-200">
            {{ scan.status === 'expired' ? 'Le délai de 15 minutes est dépassé. Téléchargez un nouveau script.' : 'La recherche ou l’analyse a échoué. Vous pouvez relancer un nouveau test.' }}
        </p>

        <details class="mt-5 text-sm text-neutral-600 dark:text-neutral-300">
            <summary class="cursor-pointer font-semibold">Que fait exactement le script ?</summary>
            <p class="mt-2 leading-relaxed">
                Il utilise uniquement les commandes Windows CIM, Get-Volume et Get-PhysicalDisk en lecture seule, envoie un JSON limité à cette session, puis ouvre cette fiche. Il ne lit ni vos fichiers, ni vos logiciels, ni vos identifiants matériels.
            </p>
        </details>
    </section>
</template>
