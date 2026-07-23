const IGDB_IMAGE_HOST = 'images.igdb.com';

export const gameCoverUrl = (coverUrl: string | null | undefined, size = 't_cover_big'): string | null => {
    const value = coverUrl?.trim();

    if (!value) {
        return null;
    }

    let normalized = value;

    if (normalized.startsWith('//')) {
        normalized = 'https:' + normalized;
    } else if (normalized.startsWith(IGDB_IMAGE_HOST + '/')) {
        normalized = 'https://' + normalized;
    } else if (normalized.startsWith('http://' + IGDB_IMAGE_HOST + '/')) {
        normalized = 'https://' + normalized.slice('http://'.length);
    }

    return normalized.replace(/\/t_[^/]+\//, '/' + size + '/');
};
