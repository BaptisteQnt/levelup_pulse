<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import { resolveBorderClass } from '@/lib/premium';

const props = defineProps<{
    user: {
        name: string;
        username: string;
        city: string | null;
        country: string | null;
        age: number;
        created_at: string;
        is_premium: boolean;
        display_name_color: string | null;
        display_alias: string | null;
        profile_border_style: string | null;
    };
}>();

const displayNameStyle = computed(() =>
    props.user.is_premium && props.user.display_name_color
        ? { color: props.user.display_name_color }
        : undefined
);

const displayAlias = computed(() =>
    props.user.is_premium && props.user.display_alias ? props.user.display_alias : null
);

const avatarBorderClass = computed(() =>
    props.user.is_premium ? resolveBorderClass(props.user.profile_border_style) : 'border border-neutral-200'
);

const initials = computed(() => props.user.name.slice(0, 2).toUpperCase());
</script>

<template>
    <Head :title="`@${user.username}`" />

    <section class="mx-auto max-w-2xl px-4 py-10">
        <div class="flex flex-col gap-6 rounded-lg border border-sidebar-border/70 bg-white/90 p-6 shadow-sm dark:bg-neutral-950/80">
            <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                <div
                    class="flex size-20 items-center justify-center rounded-full bg-neutral-100 text-2xl font-semibold uppercase text-neutral-500 shadow-sm dark:bg-neutral-800"
                    :class="avatarBorderClass"
                >
                    {{ initials }}
                </div>
                <div class="space-y-1">
                    <h1 class="text-3xl font-bold" :style="displayNameStyle">
                        {{ user.name }}
                        <span v-if="displayAlias" class="ml-2 text-base font-medium text-muted-foreground">
                            ({{ displayAlias }})
                        </span>
                    </h1>
                    <p class="text-neutral-600 dark:text-neutral-300">@{{ user.username }}</p>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        Membre depuis {{ user.created_at }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 text-sm text-neutral-600 dark:text-neutral-300">
                <p><span class="font-medium text-neutral-800 dark:text-neutral-100">Âge :</span> {{ user.age }} ans</p>
                <p>
                    <span class="font-medium text-neutral-800 dark:text-neutral-100">Localisation :</span>
                    {{ user.country || 'Non renseignée' }}
                </p>
                <p v-if="user.city">
                    <span class="font-medium text-neutral-800 dark:text-neutral-100">Ville :</span> {{ user.city }}
                </p>
            </div>
        </div>
    </section>
</template>
