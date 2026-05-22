import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

function normalizePath(value: string): string {
    if (!value) {
        return '/';
    }

    const [path] = value.split(/[?#]/);
    const withLeadingSlash = path.startsWith('/') ? path : `/${path}`;
    const trimmed = withLeadingSlash.replace(/\/+$/, '');

    return trimmed === '' ? '/' : trimmed;
}

export function useActiveLink() {
    const page = usePage<SharedData>();

    const resolveCurrentOrigin = (): string | undefined => {
        const location = page.props.ziggy?.location;

        if (location) {
            try {
                return new URL(location).origin;
            } catch {
                // Ignore parsing errors and fall back to the window origin when available.
            }
        }

        if (typeof window !== 'undefined' && window.location) {
            return window.location.origin;
        }

        return undefined;
    };

    const resolveHref = (href: string, origin: string | undefined) => {
        const base = origin ?? (typeof window !== 'undefined' && window.location ? window.location.origin : 'http://localhost');

        try {
            const url = new URL(href, base);

            return { origin: url.origin, pathname: url.pathname };
        } catch {
            return { origin, pathname: href };
        }
    };

    const isActive = (href: string): boolean => {
        if (!href) {
            return false;
        }

        const currentOrigin = resolveCurrentOrigin();
        const { origin: targetOrigin, pathname } = resolveHref(href, currentOrigin);

        if (targetOrigin && currentOrigin && targetOrigin !== currentOrigin) {
            return false;
        }

        const targetPath = normalizePath(pathname);
        const currentPath = normalizePath(page.url);

        if (targetPath === '/') {
            return currentPath === '/';
        }

        return currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
    };

    return { isActive };
}
