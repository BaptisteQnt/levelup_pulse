<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="header">
        <div class="flex min-h-screen w-full flex-col">
            <AppHeader :breadcrumbs="breadcrumbs" />
            <AppContent class="w-full px-4 py-6 md:px-6 lg:px-8">
                <slot />
            </AppContent>
            <AppFooter />
        </div>
    </AppShell>
</template>
