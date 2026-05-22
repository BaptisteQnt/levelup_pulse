import type { User } from '@/types';

export const premiumBorderClassMap: Record<string, string> = {
    none: 'border border-sidebar-border/80',
    starlight: 'ring-2 ring-violet-400 shadow-[0_0_14px_rgba(167,139,250,0.75)] border-transparent',
    neon: 'ring-2 ring-cyan-400 shadow-[0_0_16px_rgba(34,211,238,0.75)] border-transparent',
    ember: 'ring-2 ring-orange-400 shadow-[0_0_16px_rgba(251,146,60,0.75)] border-transparent',
};

export const premiumBorderOptions = [
    { value: 'none', label: 'Aucune bordure' },
    { value: 'starlight', label: 'Halo stellaire' },
    { value: 'neon', label: 'Éclat néon' },
    { value: 'ember', label: 'Lueur braise' },
];

export function resolveBorderClass(style?: string | null): string {
    if (!style) {
        return premiumBorderClassMap.none;
    }

    return premiumBorderClassMap[style] ?? premiumBorderClassMap.none;
}

export function isPremiumUser(user?: Pick<User, 'is_subscribed'> | null): boolean {
    return Boolean(user?.is_subscribed);
}

export function resolveNameColor(user?: Pick<User, 'display_name_color' | 'is_subscribed'> | null): string | undefined {
    if (!user?.is_subscribed) {
        return undefined;
    }

    return user.display_name_color ?? undefined;
}

export function resolveAlias(user?: Pick<User, 'display_alias' | 'is_subscribed'> | null): string | undefined {
    if (!user?.is_subscribed) {
        return undefined;
    }

    const alias = user.display_alias?.trim();

    return alias ? alias : undefined;
}
