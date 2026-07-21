<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const useRecoveryCode = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const submit = () => {
    form.post(route('two-factor.login.store'), {
        onFinish: () => form.reset('code', 'recovery_code'),
    });
};
</script>

<template>
    <AuthBase
        title="Validation en deux étapes"
        description="Saisissez le code de votre application d'authentification pour continuer."
    >
        <Head title="Double authentification" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div v-if="!useRecoveryCode" class="grid gap-2">
                <Label for="code">Code d'authentification</Label>
                <Input
                    id="code"
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                    placeholder="123456"
                />
                <InputError :message="form.errors.code" />
            </div>

            <div v-else class="grid gap-2">
                <Label for="recovery_code">Code de récupération</Label>
                <Input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    autocomplete="one-time-code"
                    autofocus
                    placeholder="code-de-recuperation"
                />
                <InputError :message="form.errors.recovery_code" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Valider
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                <button type="button" class="underline underline-offset-4" @click="useRecoveryCode = !useRecoveryCode">
                    {{ useRecoveryCode ? 'Utiliser un code temporaire' : 'Utiliser un code de récupération' }}
                </button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                <TextLink :href="route('login')">Retour à la connexion</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
