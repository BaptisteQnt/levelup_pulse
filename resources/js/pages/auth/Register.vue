<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    over18DeclarationLabel: string;
    rgpdConsentLabel: string;
}>();

const form = useForm({
    email: '',
    password: '',
    over_18_declared: false,
    rgpd_consent: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Créez votre compte" description="Renseignez vos informations principales pour commencer l'aventure">
        <Head title="Inscription" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Adresse e-mail</Label>
                    <Input id="email" type="email" required autofocus autocomplete="email" v-model="form.email" placeholder="votre@adresse.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Mot de passe</Label>
                    <Input id="password" type="password" required autocomplete="new-password" v-model="form.password" placeholder="Mot de passe" />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <label for="over_18_declared" class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed">
                        <input
                            id="over_18_declared"
                            v-model="form.over_18_declared"
                            type="checkbox"
                            required
                            class="mt-1 h-4 w-4 shrink-0 rounded border-input text-primary focus:ring-primary"
                        />
                        <span>{{ over18DeclarationLabel }}</span>
                    </label>
                    <InputError :message="form.errors.over_18_declared" />
                </div>

                <div class="grid gap-2">
                    <label for="rgpd_consent" class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed">
                        <input
                            id="rgpd_consent"
                            v-model="form.rgpd_consent"
                            type="checkbox"
                            required
                            class="mt-1 h-4 w-4 shrink-0 rounded border-input text-primary focus:ring-primary"
                        />
                        <span>
                            {{ rgpdConsentLabel }}
                            <Link :href="route('legal.privacy')" class="font-medium text-primary underline underline-offset-4">
                                Consulter la politique de confidentialité
                            </Link>
                        </span>
                    </label>
                    <InputError :message="form.errors.rgpd_consent" />
                </div>

                <div class="mt-4 space-y-2">
                    <a href="/auth/google/redirect" class="inline-flex w-full justify-center rounded border px-3 py-2">
                        Continuer avec Google
                    </a>
                    <a href="/auth/discord/redirect" class="inline-flex w-full justify-center rounded border px-3 py-2">
                        Continuer avec Discord
                    </a>
                </div>

                <Button type="submit" class="mt-4 w-full" :disabled="form.processing || !form.over_18_declared || !form.rgpd_consent">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Créer mon compte
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Déjà inscrit ?
                <TextLink :href="route('login')" class="underline underline-offset-4">Se connecter</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
