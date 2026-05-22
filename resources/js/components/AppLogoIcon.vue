<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img
        :class="className"
        src="/images/logo-levelup.png"
        alt="LevelUp logo"
        v-bind="$attrs"
    />
</template>
