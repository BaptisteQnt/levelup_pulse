import { router } from '@inertiajs/vue3';
import { getXsrfToken } from './lib/utils';

const token = document.head.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');

if (token) {
    router.on('before', (event) => {
        const headers = event.detail.visit.headers ?? {};

        headers['X-Requested-With'] = 'XMLHttpRequest';
        headers['X-CSRF-TOKEN'] = token.content;

        const xsrfToken = getXsrfToken();

        if (xsrfToken) {
            headers['X-XSRF-TOKEN'] = xsrfToken;
        }

        event.detail.visit.headers = headers;
    });
} else {
    console.warn('CSRF token not found. Inertia requests may be rejected.');
}
