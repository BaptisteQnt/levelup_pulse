<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Link } from '@inertiajs/vue3';

interface FooterLink {
    title: string;
    href: string;
}

const footerLinks: FooterLink[] = [
    {
        title: 'Accueil',
        href: '/',
    },
    {
        title: 'Jeux',
        href: '/games',
    },
    {
        title: 'Premium',
        href: '/premium',
    },
    {
        title: 'Présentation',
        href: '/presentation',
    },
];

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="mt-auto border-t border-[#0E6BA8]/40 bg-[#001C55] text-white">
        <div class="mx-auto w-full max-w-7xl px-4 py-10">
            <div class="flex flex-col gap-10 md:flex-row md:items-start md:justify-between">
                <div class="flex flex-col gap-4 text-sm text-white/80">
                    <Link :href="route('home')" class="flex items-center gap-3 text-base font-semibold">
                        <AppLogo />
                        <span class="sr-only">Retour à l'accueil</span>
                    </Link>
                    <p>
                        Mentions légales : LevelUp est un espace communautaire dédié à la découverte et au partage de jeux vidéo.
                        Toutes les informations présentées sont fournies à titre indicatif.
                    </p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Navigation</h2>
                    <ul class="mt-4 flex flex-col gap-2 text-sm">
                        <li v-for="item in footerLinks" :key="item.title">
                            <Link :href="item.href" class="transition hover:text-white/90">
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-10 flex flex-col gap-4 border-t border-white/30 pt-6 text-sm text-white/80 md:flex-row md:items-center md:justify-between">
                <p>© {{ currentYear }} LevelUp. Tous droits réservés.</p>
                <div class="flex flex-wrap gap-4">
                    <Link :href="route('legal.mentions')" class="underline underline-offset-4 transition hover:text-white">
                        Mentions légales
                    </Link>
                    <Link :href="route('legal.privacy')" class="underline underline-offset-4 transition hover:text-white">
                        Politique de confidentialité
                    </Link>
                    <Link :href="route('legal.terms')" class="underline underline-offset-4 transition hover:text-white">
                        Conditions générales de vente
                    </Link>
                    <Link :href="route('legal.cookies')" class="underline underline-offset-4 transition hover:text-white">
                        Politique des cookies
                    </Link>
                </div>
            </div>
        </div>
    </footer>
</template>
