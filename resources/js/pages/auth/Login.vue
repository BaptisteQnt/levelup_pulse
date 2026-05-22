<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Connectez-vous à votre compte" description="Saisissez votre adresse e-mail et votre mot de passe pour continuer">
        <Head title="Connexion" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Adresse e-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="votre@adresse.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Mot de passe</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" :tabindex="3">
                            Mot de passe oublié ?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Mot de passe"
                    />
                    <InputError :message="form.errors.password" />
                </div>
                <div class="mt-4 space-y-2">
                    <a href="/auth/google/redirect" class="inline-flex w-full justify-center rounded border px-3 py-2">
                        Continuer avec Google
                    </a>
                    <a href="/auth/discord/redirect" class="inline-flex w-full justify-center rounded border px-3 py-2">
                        Continuer avec Discord
                    </a>
                </div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Se connecter
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Pas encore de compte ?
                <TextLink :href="route('register')" :tabindex="5">Créer un compte</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
