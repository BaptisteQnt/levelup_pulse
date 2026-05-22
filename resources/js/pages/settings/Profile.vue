<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name ?? '',
    username: user.username ?? '',
    email: user.email ?? '',
    phone: user.phone ?? '',
    address: user.address ?? '',
    city: user.city ?? '',
    cp: user.cp ?? '',
    country: user.country ?? '',
    age: user.age ?? '',
});


const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Information du profil" description="Mettre à jour mes informations" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nom</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Nom complet" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="username">Nom d'utilisateur</Label>
                        <Input id="username" class="mt-1 block w-full" v-model="form.username" required placeholder="Nom utilisateur" />
                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="phone">Téléphone</Label>
                        <Input id="phone" class="mt-1 block w-full" v-model="form.phone" placeholder="Numéros de téléphone" />
                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="address">Adresse</Label>
                        <Input id="address" class="mt-1 block w-full" v-model="form.address" placeholder="Addresse" />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="city">Ville</Label>
                        <Input id="city" class="mt-1 block w-full" v-model="form.city" placeholder="Ville" />
                        <InputError class="mt-2" :message="form.errors.city" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="cp">Code postal</Label>
                        <Input id="cp" class="mt-1 block w-full" v-model="form.cp" placeholder="Code postal" />
                        <InputError class="mt-2" :message="form.errors.cp" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="country">Pays</Label>
                        <Input id="country" class="mt-1 block w-full" v-model="form.country" placeholder="Pays" />
                        <InputError class="mt-2" :message="form.errors.country" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="age">Âge</Label>
                        <Input id="age" class="mt-1 block w-full" v-model="form.age" type="number" placeholder="Age" />
                        <InputError class="mt-2" :message="form.errors.age" />
                    </div>


                    <div class="grid gap-2">
                        <Label for="email">Adresse e-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Adresse² e-mail"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Votre email n'est pas vérifié.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Cliquez ici pour renvoyer l'e-mail de vérification.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Sauvegarder</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Sauvegarder</p>
                        </Transition>
                    </div>
                </form>

                <div class="space-y-4 rounded-lg border border-primary/20 bg-primary/5 p-4">
                    <div>
                        <h3 class="text-base font-semibold text-primary">Personnalisation premium</h3>
                        <p class="text-sm text-muted-foreground">
                            Débloquez la couleur de votre nom, un pseudo affiché et des bordures animées depuis l'onglet Premium.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button as-child>
                            <Link href="/settings/premium">Ouvrir l'onglet Premium</Link>
                        </Button>
                        <p v-if="!user.is_subscribed" class="text-xs text-muted-foreground">
                            Premium est requis pour accéder aux options de personnalisation avancées.
                        </p>
                        <p v-else class="text-xs text-muted-foreground">
                            Vous êtes abonné : ajustez vos préférences depuis l'onglet Premium.
                        </p>
                    </div>
                </div>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
