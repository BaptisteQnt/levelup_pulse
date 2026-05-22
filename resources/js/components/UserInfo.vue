<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import { isPremiumUser, resolveAlias, resolveBorderClass, resolveNameColor } from '@/lib/premium';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');

const isPremium = computed(() => isPremiumUser(props.user));
const nameColor = computed(() => resolveNameColor(props.user));
const premiumAlias = computed(() => resolveAlias(props.user));
const avatarBorderClass = computed(() =>
    isPremium.value ? resolveBorderClass(props.user.profile_border_style) : ''
);
const nameStyle = computed(() => (nameColor.value ? { color: nameColor.value } : undefined));
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-full" :class="avatarBorderClass">
        <AvatarImage v-if="showAvatar" :src="user.avatar" :alt="user.name" />
        <AvatarFallback class="rounded-full text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium" :style="nameStyle">
            {{ user.name }}
            <template v-if="premiumAlias">
                <span class="ml-1 text-xs font-normal text-muted-foreground">({{ premiumAlias }})</span>
            </template>
        </span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
    </div>
</template>
