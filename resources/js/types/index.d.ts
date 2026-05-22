import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SiteAnnouncement {
    id: number;
    title: string;
    content: string;
    published_at: string | null;
    author: {
        id: number;
        name: string | null;
        username: string;
    } | null;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    announcement?: SiteAnnouncement | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    username: string;
    avatar?: string | null;
    phone?: string | null;
    address?: string | null;
    city?: string | null;
    cp?: string | null;
    country?: string | null;
    age?: number | null;
    display_name_color?: string | null;
    display_alias?: string | null;
    profile_border_style?: string | null;
    is_subscribed?: boolean;
    is_profile_complete?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    is_admin: boolean;
}

export type BreadcrumbItemType = BreadcrumbItem;
