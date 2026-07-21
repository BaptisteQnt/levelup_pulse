<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getCsrfToken, getXsrfToken } from '@/lib/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, RefreshCw, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    passwordConfirmed: boolean;
    twoFactorEnabled: boolean;
    twoFactorConfirmed: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Sécurité',
        href: '/settings/security',
    },
];

const qrCodeSvg = ref<string | null>(null);
const recoveryCodes = ref<string[]>([]);
const loadingDetails = ref(false);

const confirmForm = useForm({
    code: '',
});

const enableForm = useForm({});
const disableForm = useForm({});
const recoveryForm = useForm({});

const statusLabel = computed(() => {
    if (props.twoFactorConfirmed) {
        return 'Active';
    }

    if (props.twoFactorEnabled) {
        return 'En attente de confirmation';
    }

    return 'Inactive';
});

const requestHeaders = () => {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const csrfToken = getCsrfToken();
    const xsrfToken = getXsrfToken();

    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    if (xsrfToken) {
        headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    return headers;
};

const fetchTwoFactorDetails = async () => {
    if (!props.twoFactorEnabled) {
        qrCodeSvg.value = null;
        recoveryCodes.value = [];
        return;
    }

    loadingDetails.value = true;

    try {
        const [qrResponse, recoveryResponse] = await Promise.all([
            fetch(route('two-factor.qr-code'), {
                credentials: 'same-origin',
                headers: requestHeaders(),
            }),
            fetch(route('two-factor.recovery-codes'), {
                credentials: 'same-origin',
                headers: requestHeaders(),
            }),
        ]);

        if (qrResponse.ok) {
            const qrPayload = await qrResponse.json();
            qrCodeSvg.value = qrPayload.svg ?? null;
        }

        if (recoveryResponse.ok) {
            recoveryCodes.value = await recoveryResponse.json();
        }
    } finally {
        loadingDetails.value = false;
    }
};

const enableTwoFactor = () => {
    enableForm.post(route('two-factor.enable'), {
        preserveScroll: true,
        onSuccess: fetchTwoFactorDetails,
    });
};

const confirmTwoFactor = () => {
    confirmForm.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => confirmForm.reset('code'),
    });
};

const regenerateRecoveryCodes = () => {
    recoveryForm.post(route('two-factor.regenerate-recovery-codes'), {
        preserveScroll: true,
        onSuccess: fetchTwoFactorDetails,
    });
};

const disableTwoFactor = () => {
    disableForm.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            qrCodeSvg.value = null;
            recoveryCodes.value = [];
        },
    });
};

onMounted(fetchTwoFactorDetails);
watch(() => props.twoFactorEnabled, () => fetchTwoFactorDetails());
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Sécurité" />

        <SettingsLayout>
            <div class="space-y-8">
                <HeadingSmall title="Double authentification" description="Ajouter une validation par code temporaire à la connexion" />

                <div class="space-y-6 rounded-lg border p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-medium">État : {{ statusLabel }}</p>
                            <p class="text-sm text-muted-foreground">
                                La double authentification protège le compte même si le mot de passe est compromis.
                            </p>
                        </div>

                        <Button v-if="!passwordConfirmed" as-child>
                            <Link :href="route('password.confirm', { return_to: '/settings/security' })">Confirmer mon mot de passe</Link>
                        </Button>

                        <Button v-else-if="!twoFactorEnabled" :disabled="enableForm.processing" @click="enableTwoFactor">
                            <LoaderCircle v-if="enableForm.processing" class="h-4 w-4 animate-spin" />
                            <ShieldCheck v-else class="h-4 w-4" />
                            Activer
                        </Button>

                        <Button v-else variant="destructive" :disabled="disableForm.processing" @click="disableTwoFactor">
                            <LoaderCircle v-if="disableForm.processing" class="h-4 w-4 animate-spin" />
                            <ShieldOff v-else class="h-4 w-4" />
                            Désactiver
                        </Button>
                    </div>

                    <div v-if="!passwordConfirmed" class="rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground">
                        Confirmez votre mot de passe avant de modifier les paramètres de double authentification.
                    </div>

                    <div v-if="passwordConfirmed && twoFactorEnabled" class="space-y-6">
                        <div v-if="loadingDetails" class="text-sm text-muted-foreground">Chargement des informations de sécurité...</div>

                        <div v-if="qrCodeSvg" class="space-y-3">
                            <p class="text-sm text-muted-foreground">
                                Scannez ce QR code avec une application d'authentification, puis confirmez avec le code généré.
                            </p>
                            <div class="w-fit rounded-md border bg-white p-3" v-html="qrCodeSvg" />
                        </div>

                        <form v-if="!twoFactorConfirmed" class="space-y-4" @submit.prevent="confirmTwoFactor">
                            <div class="grid gap-2">
                                <Label for="code">Code de confirmation</Label>
                                <Input
                                    id="code"
                                    v-model="confirmForm.code"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    placeholder="123456"
                                />
                                <InputError :message="confirmForm.errors.code" />
                            </div>

                            <Button type="submit" :disabled="confirmForm.processing">
                                <LoaderCircle v-if="confirmForm.processing" class="h-4 w-4 animate-spin" />
                                Confirmer
                            </Button>
                        </form>

                        <div v-if="recoveryCodes.length" class="space-y-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium">Codes de récupération</p>
                                    <p class="text-sm text-muted-foreground">Conservez ces codes dans un endroit sûr.</p>
                                </div>

                                <Button variant="outline" :disabled="recoveryForm.processing" @click="regenerateRecoveryCodes">
                                    <LoaderCircle v-if="recoveryForm.processing" class="h-4 w-4 animate-spin" />
                                    <RefreshCw v-else class="h-4 w-4" />
                                    Régénérer
                                </Button>
                            </div>

                            <div class="grid gap-2 rounded-md bg-muted p-3 font-mono text-sm sm:grid-cols-2">
                                <span v-for="code in recoveryCodes" :key="code">{{ code }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
