<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    textClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
    textClass: 'text-[var(--foreground)] dark:text-white',
});
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
        <AppLogoIcon class="size-5 object-contain" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm" :class="props.textClass">
        <span class="mb-0.5 truncate font-semibold leading-none">LevelUp</span>
    </div>
</template>
