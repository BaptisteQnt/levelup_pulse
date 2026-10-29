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

  it('shows the movable IoT card with the interpreted sensor state', async () => {
    globalThis.__INERTIA_PAGE__ = {
      props: pageProps(
        { is_admin: false, is_super_admin: false },
        {
          latestTelemetry: {
            device_id: 'CAPTEUR-SALON',
            temperature: 38.5,
            humidity: 47,
            measured_at: '2026-10-27T12:30:00+01:00',
            received_at: '2026-10-27T12:30:02+01:00',
            device_last_seen_at: '2026-10-27T12:30:15+01:00',
            status: 'danger',
            message: 'Température dangereuse : vérifiez immédiatement l’environnement du capteur.',
            safe_temperature_min: 5,
            safe_temperature_max: 35,
            ai_enabled: true,
            ai_analysis: {
              status: 'completed',
              reliability: 'suspicious',
              approved_for_use: false,
              risk_level: 'high',
              risk_summary: 'La chaleur peut augmenter la fatigue pendant une session prolongée.',
              reliability_reason: 'La hausse est brutale par rapport aux derniers relevés.',
              recommendations: ['Aérer la pièce.', 'Vérifier avec un second thermomètre.'],
              disclaimer: 'Analyse informative.',
              analyzed_at: '2026-10-27T12:30:10+01:00',
            },
          },
        },
      ),
      url: '/dashboard',
    };

    const { html } = await renderDashboard();

    expect(html).toContain('Capteur en direct');
    expect(html).toContain('CAPTEUR-SALON');
    expect(html).toContain('Température dangereuse');
    expect(html).toContain('38,5 °C');
    expect(html).toContain('47 %');
    expect(html).toContain('Analyse IA');
    expect(html).toContain('Risque élevé');
    expect(html).toContain('Mesure suspecte');
  });

  it('distinguishes a received measurement whose analysis is not required', async () => {
    globalThis.__INERTIA_PAGE__ = {
      props: pageProps(
        { is_admin: false, is_super_admin: false },
        {
          latestTelemetry: {
            device_id: 'CAPTEUR-SALON',
            temperature: 24,
            humidity: 50,
            measured_at: null,
            received_at: '2026-10-27T12:30:02+01:00',
            device_last_seen_at: '2026-10-27T12:30:15+01:00',
            status: 'safe',
            message: 'Température de l’air mesurée dans la plage configurée.',
            safe_temperature_min: 5,
            safe_temperature_max: 35,
            ai_enabled: true,
            ai_analysis: {
              status: 'not_required',
              reliability: null,
              approved_for_use: false,
              risk_level: null,
              risk_summary: null,
              reliability_reason: null,
              recommendations: [],
              disclaimer: null,
              analyzed_at: null,
            },
            last_ai_verdict: null,
          },
        },
      ),
      url: '/dashboard',
    };

    const { html } = await renderDashboard();

    expect(html).toContain('Mesure reçue');
    expect(html).toContain('Analyse non requise pour cette mesure');
    expect(html).toContain('Fiabilité non évaluée');
    expect(html).not.toContain('Mesure cohérente');
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
