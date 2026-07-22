import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { run } from 'axe-core';
import { toHaveNoViolations } from 'vitest-axe';
import { createTestServer } from './helpers/createServer.js';
import { renderComponent } from './helpers/renderComponent.js';

expect.extend({ toHaveNoViolations });

const stats = {
  games: { total: 42, rated_total: 19 },
  ratings: { total: 128, average: 8.4 },
  articles: { published_total: 24, premium_total: 6 },
  users: { total: 315 },
};

const articles = [
  {
    id: 1,
    title: 'La grande aventure du moment',
    slug: 'grande-aventure',
    excerpt: 'Une exploration complète du jeu qui fait parler toute la communauté.',
    image_url: '/storage/articles/feature.jpg',
    is_premium: true,
    published_at: '2026-07-21T10:00:00+00:00',
    game: { title: 'Pulse Quest', slug: 'pulse-quest', cover_url: null },
    author: { name: 'Alice', username: 'alice' },
  },
  {
    id: 2,
    title: 'Les secrets de la nouvelle saison',
    slug: 'nouvelle-saison',
    excerpt: 'Les nouveautés importantes à retenir.',
    image_url: null,
    is_premium: false,
    published_at: '2026-07-20T10:00:00+00:00',
    game: {
      title: 'Season Arena',
      slug: 'season-arena',
      cover_url: '//images.igdb.com/igdb/image/upload/t_thumb/season.jpg',
    },
    author: { name: null, username: 'bob' },
  },
];

const games = [
  {
    id: 10,
    title: 'Neon Horizon',
    slug: 'neon-horizon',
    cover_url: '//images.igdb.com/igdb/image/upload/t_thumb/neon.jpg',
    searched_at: '2026-07-22T08:00:00+00:00',
  },
];

const pageProps = (user = null, overrides = {}) => ({
  auth: { user },
  announcement: null,
  latestArticles: articles,
  recentGames: games,
  stats,
  ...overrides,
});

describe('Editorial dashboard', () => {
  let server;

  beforeEach(async () => {
    server = await createTestServer();
    vi.stubGlobal('route', (name, parameter) => `${name}${parameter ? `/${parameter}` : ''}`);
    globalThis.__INERTIA_PAGE__ = { props: pageProps(), url: '/' };
  });

  afterEach(async () => {
    await server?.close();
    vi.restoreAllMocks();
    delete globalThis.__INERTIA_PAGE__;
  });

  const renderDashboard = async () => {
    const mod = await server.ssrLoadModule('/resources/js/pages/Dashboard.vue');
    return renderComponent(mod.default);
  };

  it('renders the feature story, secondary content, games and all available stats', async () => {
    const { html } = await renderDashboard();

    expect(html).toContain('La grande aventure du moment');
    expect(html).toContain('Les secrets de la nouvelle saison');
    expect(html).toContain('Neon Horizon');
    expect(html).toContain('Note moyenne');
    expect(html).toContain('8,4 / 10');
    expect(html).toContain('Jeux notés');
    expect(html).toContain('Communauté');
    expect(html).not.toContain('Outils d’administration');
  });

  it('shows administration tools only to administrators', async () => {
    globalThis.__INERTIA_PAGE__ = {
      props: pageProps({ is_admin: true, is_super_admin: false }),
      url: '/dashboard',
    };

    const { html } = await renderDashboard();

    expect(html).toContain('Outils d’administration');
    expect(html).toContain('Demandes RGPD');
  });

  it('renders intentional empty states when there is no editorial content', async () => {
    globalThis.__INERTIA_PAGE__ = {
      props: pageProps(null, { latestArticles: [], recentGames: [] }),
      url: '/',
    };

    const { html } = await renderDashboard();

    expect(html).toContain('La prochaine histoire se prépare.');
    expect(html).toContain('La bibliothèque se prépare.');
  });

  it('has no automated accessibility violations in its primary state', async () => {
    const { html } = await renderDashboard();
    const results = await run(html);

    expect(results).toHaveNoViolations();
  });
});
