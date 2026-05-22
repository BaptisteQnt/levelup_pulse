<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';

import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import { useActiveLink } from '@/composables/useActiveLink';
import { isPremiumUser, resolveAlias, resolveBorderClass, resolveNameColor } from '@/lib/premium';
import type { BreadcrumbItem, NavItem, SharedData, User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Menu, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const user = computed<User | null>(() => page.props.auth.user);
const { getInitials } = useInitials();
const { isActive } = useActiveLink();

const userInitials = computed(() => (user.value ? getInitials(user.value.name) : ''));
const userHasAvatar = computed(() => Boolean(user.value?.avatar));

const premiumAlias = computed(() => (user.value ? resolveAlias(user.value) : undefined));
const premiumNameColor = computed(() => (user.value ? resolveNameColor(user.value) : undefined));
const avatarBorderClass = computed(() =>
    user.value && isPremiumUser(user.value)
        ? resolveBorderClass(user.value.profile_border_style)
        : ''
);


const mainNavItems: NavItem[] = [
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

const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

const adminNavItems = computed<NavItem[]>(() => {
    if (!page.props.auth.user?.is_admin) {
        return [];
    }

    return [
        {
            title: 'Dashboard',
            href: route('dashboard'),
        },
        {
            title: 'Modération',
            href: route('admin.moderation.index'),
        },
        {
            title: 'Pouvoirs',
            href: route('admin.powers.index'),
        },
        {
            title: 'Annonces',
            href: route('admin.announcements.index'),
        },
        {
            title: 'Demandes RGPD',
            href: route('admin.privacy.requests.index'),
        },
    ];
});
</script>

<template>
    <div class="border-b border-[#0E6BA8]/40 bg-[#001C55] text-white backdrop-blur dark:border-[#A6E1FA]/40 dark:bg-[#001C55] dark:text-white">
        <div class="mx-auto flex h-16 items-center justify-between px-4 md:max-w-7xl">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md p-2 transition hover:bg-[#0E6BA8]/10 focus:outline-none focus:ring-2 focus:ring-primary lg:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="primary-navigation"
                    @click="toggleMobileMenu"
                >
                    <span class="sr-only">Basculer la navigation</span>
                    <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <X v-else class="h-6 w-6" />
                </button>
                <Link :href="route('home')" class="flex items-center gap-2" @click="closeMobileMenu">
                    <AppLogo text-class="text-white" />
                </Link>
            </div>

            <nav
                class="hidden items-center gap-6 text-sm font-medium text-white lg:flex"
                aria-label="Navigation principale"
            >
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="transition"
                    :class="
                        (item.isActive ?? isActive(item.href))
                            ? 'font-semibold text-white'
                            : 'text-white/80 hover:text-white'
                    "
                >
                    {{ item.title }}
                </Link>
                <Link
                    v-if="!user"
                    :href="route('login')"
                    class="rounded-lg bg-primary px-4 py-2 font-semibold text-white shadow hover:bg-primary/90"
                >
                    Connexion
                </Link>
                <template v-else>
                    <DropdownMenu v-if="adminNavItems.length">
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-white/70 px-3 py-2 font-semibold text-white transition hover:border-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <span>Administration</span>
                                <ChevronDown class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="min-w-56" align="end" :side-offset="12">
                            <DropdownMenuLabel>Accès administrateur</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem v-for="item in adminNavItems" :key="item.title" :as-child="true">
                                <Link :href="item.href" class="flex items-center justify-between gap-2">
                                    <span>{{ item.title }}</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-full border border-white/70 p-1.5 transition hover:border-white focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <Avatar class="size-8" :class="avatarBorderClass">
                                    <AvatarImage v-if="userHasAvatar" :src="user?.avatar" :alt="user?.name" />
                                    <AvatarFallback>{{ userInitials }}</AvatarFallback>
                                </Avatar>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="min-w-64" align="end" :side-offset="12">
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
            </nav>
        </div>

        <div
            v-if="mobileMenuOpen"
            id="primary-navigation"
            class="border-t border-[#0E6BA8]/40 bg-[#001C55] px-4 py-4 text-white shadow-lg lg:hidden dark:border-[#A6E1FA]/40 dark:bg-[#001C55] dark:text-white"
        >
            <div class="flex flex-col gap-3 text-base font-medium">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="rounded-lg px-3 py-2 transition hover:bg-[#0E6BA8]/20"
                    :class="
                        (item.isActive ?? isActive(item.href))
                            ? 'bg-[#0E6BA8]/30 font-semibold text-white dark:bg-[#0E6BA8]/40 dark:text-white'
                            : 'text-white/80 dark:text-white/80'
                    "
                    @click="closeMobileMenu"
                >
                    {{ item.title }}
                </Link>
                <Link
                    v-if="!user"
                    :href="route('login')"
                    class="rounded-lg bg-primary px-3 py-2 text-center font-semibold text-white hover:bg-primary/90"
                    @click="closeMobileMenu"
                >
                    Connexion
                </Link>
                <div v-else class="flex flex-col gap-3 rounded-lg border border-white/40 p-3">
                    <div class="flex items-center gap-3">
                        <Avatar class="size-10" :class="avatarBorderClass">
                            <AvatarImage v-if="userHasAvatar" :src="user?.avatar" :alt="user?.name" />
                            <AvatarFallback class="text-base">{{ userInitials }}</AvatarFallback>
                        </Avatar>
                        <div class="flex flex-col text-sm">
                            <span
                                class="font-semibold text-white"
                                :style="premiumNameColor ? { color: premiumNameColor } : undefined"
                            >
                                {{ user?.name }}
                                <span v-if="premiumAlias" class="ml-1 text-xs font-normal text-muted-foreground">
                                    ({{ premiumAlias }})
                                </span>
                            </span>
                            <span class="text-white/80">{{ user?.email }}</span>
                        </div>
                    </div>
                    <Link
                        :href="route('profile.edit')"
                        class="rounded-lg border border-white/60 px-3 py-2 text-center font-medium text-white transition hover:bg-white/10"
                        @click="closeMobileMenu"
                    >
                        Mon profil
                    </Link>
                    <Link
                        method="post"
                        as="button"
                        type="button"
                        :href="route('logout')"
                        class="rounded-lg bg-red-500 px-3 py-2 text-center font-semibold text-white hover:bg-red-500/90"
                        @click="closeMobileMenu"
                    >
                        Déconnexion
                    </Link>
                </div>
                <div v-if="adminNavItems.length" class="rounded-lg border border-white/40 p-3 text-white">
                    <p class="text-sm font-semibold text-white">Administration</p>
                    <div class="mt-2 flex flex-col gap-2">
                        <Link
                            v-for="item in adminNavItems"
                            :key="`mobile-admin-${item.title}`"
                            :href="item.href"
                            class="rounded-md px-3 py-2 text-sm transition hover:bg-[#0E6BA8]/20 dark:hover:bg-white/10"
                            @click="closeMobileMenu"
                        >
                            {{ item.title }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-t border-sidebar-border/70 bg-white dark:bg-white">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-[#0E6BA8] dark:text-[#001C55] md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
