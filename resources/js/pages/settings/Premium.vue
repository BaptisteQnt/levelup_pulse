<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { premiumBorderOptions, resolveBorderClass } from '@/lib/premium';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Premium settings',
        href: '/settings/premium',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User | null;

const form = useForm({
    name: user?.name ?? '',
    username: user?.username ?? '',
    email: user?.email ?? '',
    phone: user?.phone ?? '',
    address: user?.address ?? '',
    city: user?.city ?? '',
    cp: user?.cp ?? '',
    country: user?.country ?? '',
    age: user?.age ?? '',
    display_name_color: user?.display_name_color ?? '',
    display_alias: user?.display_alias ?? '',
    profile_border_style: user?.profile_border_style ?? 'none',
});

const isSubscribed = computed(() => Boolean(user?.is_subscribed));
const hasProfileRequirements = computed(() => {
    if (!user) {
        return false;
    }

    return Boolean(user.name && user.username && user.email && user.age);
});

const borderOptions = premiumBorderOptions;

const previewColor = computed(() => form.display_name_color || '#1f2937');

const selectedBorderClass = computed(() => resolveBorderClass(form.profile_border_style));

const previewInitials = computed(() => {
    const source = user?.name || user?.username;
    return (source ?? 'U').slice(0, 2).toUpperCase();
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Premium settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Premium"
                    description="Gérez votre abonnement et personnalisez votre profil."
                />

                <div
                    v-if="!isSubscribed"
                    class="space-y-4 rounded-lg border border-primary/20 bg-primary/5 p-6"
                >
                    <div class="space-y-2">
                        <h3 class="text-lg font-semibold text-primary">Passez au Premium</h3>
                        <p class="text-sm text-muted-foreground">
                            Débloquez l'accès aux couleurs personnalisées, au pseudo affiché et aux bordures animées.
                        </p>
                    </div>

                    <ul class="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        <li>Mettez votre nom en avant avec la couleur de votre choix.</li>
                        <li>Ajoutez un pseudo affiché pour renforcer votre identité.</li>
                        <li>Personnalisez la bordure de votre avatar avec des effets exclusifs.</li>
                    </ul>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button as-child>
                            <Link :href="route('billing.plans')">Voir les offres Premium</Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link :href="route('profile.edit')">Compléter mon profil</Link>
                        </Button>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        Une fois l'abonnement activé, revenez sur cet onglet pour personnaliser votre apparence.
                    </p>
                </div>

                <template v-else>
                    <div class="space-y-4 rounded-lg border border-primary/20 bg-primary/5 p-6">
                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-primary">Gérer mon abonnement</h3>
                            <p class="text-sm text-muted-foreground">
                                Accédez au portail de facturation pour mettre à jour votre formule ou vos informations.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button as-child>
                                <Link :href="route('billing.portal')">Ouvrir le portail de facturation</Link>
                            </Button>
                            <Button as-child variant="outline">
                                <Link :href="route('billing.plans')">Consulter les offres</Link>
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="!hasProfileRequirements"
                        class="space-y-4 rounded-lg border border-destructive/30 bg-destructive/5 p-6"
                    >
                        <div class="space-y-2">
                            <h3 class="text-base font-semibold text-destructive">
                                Complétez vos informations de profil
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Terminez de renseigner votre profil (nom, email, âge...) dans l'onglet Profil avant de
                                personnaliser votre apparence.
                            </p>
                        </div>

                        <Button as-child variant="outline">
                            <Link href="/settings/profile">Aller à l'onglet Profil</Link>
                        </Button>
                    </div>

                    <form v-else @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <h3 class="text-base font-semibold">Personnalisation avancée</h3>
                            <p class="text-sm text-muted-foreground">
                                Appliquez vos préférences et rendez votre profil unique au sein de la communauté.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="display_alias">Pseudo affiché</Label>
                            <Input
                                id="display_alias"
                                class="mt-1 block w-full"
                                v-model="form.display_alias"
                                placeholder="Ex. Le Stratège"
                            />
                            <InputError class="mt-2" :message="form.errors.display_alias" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="display_name_color">Couleur du nom</Label>
                            <div class="flex flex-wrap items-center gap-3">
                                <Input
                                    id="display_name_color"
                                    class="mt-1 w-36"
                                    v-model="form.display_name_color"
                                    placeholder="#6366f1"
                                />
                                <input
                                    type="color"
                                    class="h-10 w-12 cursor-pointer rounded border border-sidebar-border/80"
                                    :value="form.display_name_color || '#6366f1'"
                                    @input="form.display_name_color = ($event.target as HTMLInputElement).value"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="form.display_name_color = ''"
                                >
                                    Réinitialiser
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Aperçu :
                                <span class="font-semibold" :style="{ color: previewColor }">
                                    {{ user?.name ?? user?.username }}
                                </span>
                                <span v-if="form.display_alias" class="text-muted-foreground">
                                    ({{ form.display_alias }})
                                </span>
                            </p>
                            <InputError class="mt-2" :message="form.errors.display_name_color" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="profile_border_style">Bordure de l'avatar</Label>
                            <div class="flex flex-wrap items-center gap-3">
                                <select
                                    id="profile_border_style"
                                    v-model="form.profile_border_style"
                                    class="mt-1 rounded-lg border border-sidebar-border/70 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary dark:bg-neutral-900"
                                >
                                    <option
                                        v-for="option in borderOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <div
                                    class="flex size-12 items-center justify-center rounded-full bg-white font-semibold uppercase text-neutral-500 shadow-sm dark:bg-neutral-900"
                                    :class="selectedBorderClass"
                                >
                                    {{ previewInitials }}
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.profile_border_style" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">Enregistrer</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Sauvegardé.</p>
                            </Transition>
                        </div>
                    </form>
                </template>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
