import { describe, expect, it } from 'vitest';
import { gameCoverUrl } from '../lib/game-images.ts';

describe('gameCoverUrl', () => {
    it('normalizes protocol-relative IGDB URLs and requests a larger cover', () => {
        expect(gameCoverUrl('//images.igdb.com/igdb/image/upload/t_thumb/co1234.jpg')).toBe(
            'https://images.igdb.com/igdb/image/upload/t_cover_big/co1234.jpg',
        );
    });

    it('keeps complete HTTPS URLs valid instead of prefixing them twice', () => {
        expect(gameCoverUrl('https://images.igdb.com/igdb/image/upload/t_thumb/co1234.jpg')).toBe(
            'https://images.igdb.com/igdb/image/upload/t_cover_big/co1234.jpg',
        );
    });

    it('upgrades IGDB HTTP URLs to HTTPS', () => {
        expect(gameCoverUrl('http://images.igdb.com/igdb/image/upload/t_thumb/co1234.jpg')).toBe(
            'https://images.igdb.com/igdb/image/upload/t_cover_big/co1234.jpg',
        );
    });

    it('returns null when no cover is available', () => {
        expect(gameCoverUrl(null)).toBe(null);
        expect(gameCoverUrl('  ')).toBe(null);
    });
});
