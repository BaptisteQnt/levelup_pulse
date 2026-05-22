const TARTEAUCITRON_CDN = 'https://cdn.jsdelivr.net/npm/tarteaucitronjs@1.15.0/tarteaucitron.min.js';

interface TarteaucitronGlobal extends Record<string, unknown> {
    init: (options: Record<string, unknown>) => void;
    job: string[];
    user: Record<string, unknown>;
    lang?: Record<string, string>;
    services?: Record<string, Record<string, unknown>>;
}

declare global {
    interface Window {
        tarteaucitron?: TarteaucitronGlobal;
        tarteaucitronForceLanguage?: string;
        tarteaucitronCustomText?: Record<string, string>;
        dataLayer?: unknown[];
    }
}

const loadTarteaucitron = (): Promise<void> => {
    if (typeof window === 'undefined') {
        return Promise.resolve();
    }

    if (window.tarteaucitron) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const existingScript = document.querySelector<HTMLScriptElement>(`script[src="${TARTEAUCITRON_CDN}"]`);

        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Unable to load tarteaucitron.js')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = TARTEAUCITRON_CDN;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Unable to load tarteaucitron.js'));
        document.head.appendChild(script);
    });
};

const FRENCH_BANNER_COPY: Record<string, string> = {
    alertBigTitle: 'Gestion des cookies',
    alertBigPrivacy:
        'Nous respectons votre vie privée. Choisissez les cookies que vous souhaitez activer pour bénéficier d\'une expérience sur mesure.',
    alertBigScroll: 'En continuant à parcourir notre site, vous acceptez l\'utilisation des cookies sélectionnés.',
    alertBigClick: 'En poursuivant votre navigation, vous acceptez l\'utilisation des cookies sélectionnés.',
    acceptAll: 'Tout accepter',
    denyAll: 'Tout refuser',
    personalize: 'Personnaliser',
    close: 'Fermer',
    save: 'Enregistrer mes choix',
    moreInfo: 'Voir la politique des cookies',
    readmoreLink: 'Voir la politique des cookies',
    mandatoryTitle: 'Cookies strictement nécessaires',
    mandatoryText:
        'Ces cookies garantissent le fonctionnement de base du site (connexion, sécurité, accessibilité) et ne peuvent pas être désactivés.',
    alertSmall: 'Gestion des cookies',
    cookieslist: 'Liste des cookies',
    disclaimer:
        'Certains services nécessitent des cookies pour fonctionner correctement. Vous pouvez modifier vos préférences à tout moment.',
    purpose: 'Finalité',
    usePersonalizedAds: 'Utiliser des publicités personnalisées',
    useNonPersonalizedAds: 'Utiliser des publicités non personnalisées',
    consentModalTitle: 'Gestion personnalisée',
    consentModalText:
        'Activez ou désactivez librement les services selon vos préférences. Vos choix seront enregistrés pendant 6 mois.',
};

const configureFrenchLocale = (tarteaucitron: TarteaucitronGlobal) => {
    tarteaucitron.lang = {
        ...tarteaucitron.lang,
        ...FRENCH_BANNER_COPY,
    };

    window.tarteaucitronCustomText = {
        ...FRENCH_BANNER_COPY,
    };

    window.tarteaucitronForceLanguage = 'fr';
};

const initTarteaucitron = () => {
    if (!window.tarteaucitron) {
        return;
    }

    window.dataLayer = window.dataLayer || [];

    configureFrenchLocale(window.tarteaucitron);

    window.tarteaucitron.init({
        privacyUrl: '/politique-cookies',
        hashtag: '#cookies',
        cookieName: 'levelup-cookies',
        orientation: 'bottom',
        groupServices: false,
        showAlertSmall: false,
        cookieslist: true,
        showIcon: false,
        adblocker: false,
        DenyAllCta: true,
        AcceptAllCta: true,
        highPrivacy: true,
        handleBrowserDNTRequest: true,
        removeCredit: true,
        moreInfoLink: true,
        mandatory: true,
        bodyPosition: 'bottom',
        readmoreLink: '/politique-cookies',
        serviceDefaultState: 'wait',
    });

    window.tarteaucitron.user.googletagmanagerId = 'GTM-TGD3JTXZ';

    const tarteServices = window.tarteaucitron.services;
    if (tarteServices?.googletagmanager) {
        Object.assign(tarteServices.googletagmanager, {
            name: 'Google Tag Manager',
            description: "Pilote le déclenchement des balises d'analyse et de marketing LevelUp.",
        });
    }

    const consentManagedServices = ['googletagmanager'];
    const job = Array.isArray(window.tarteaucitron.job) ? window.tarteaucitron.job : [];

    consentManagedServices.forEach((service) => {
        if (!job.includes(service)) {
            job.push(service);
        }
    });

    window.tarteaucitron.job = job;
};

export const setupCookieConsent = async () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        await loadTarteaucitron();
        initTarteaucitron();
    } catch (error) {
        console.error(error);
    }
};

if (typeof window !== 'undefined') {
    void setupCookieConsent();
}

export default setupCookieConsent;
