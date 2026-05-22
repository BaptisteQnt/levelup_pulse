import { getCsrfToken, getXsrfToken } from '@/lib/utils';


export interface DashboardStats {
    games: {
        total: number;
        rated_total: number;
    };
    ratings: {
        total: number;
        average: number | null;
    };
    comments: {
        approved_total: number;
    };
    tips: {
        approved_total: number;
    };
    users: {
        total: number;
    };
}

export const fetchDashboardStats = async (): Promise<DashboardStats> => {
    const csrfToken = getCsrfToken();
    const xsrfToken = getXsrfToken();


    const response = await fetch(route('api.stats'), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
            ...(xsrfToken && { 'X-XSRF-TOKEN': xsrfToken }),

        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error("Impossible de récupérer les statistiques du tableau de bord.");
    }

    const payload = (await response.json()) as DashboardStats;

    return payload;
};
