import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { reactive } from 'vue';
import { createTestServer } from './helpers/createServer.js';
import { renderComponent } from './helpers/renderComponent.js';
import { __setFormQueue } from './mocks/inertia.js';

function createMockForm(initial) {
  return reactive({
    ...initial,
    errors: {},
    processing: false,
    __submissions: [],
    post(url, options = {}) {
      this.__submissions.push({ method: 'post', url, options });
      if (options.onSuccess) options.onSuccess();
      if (options.onFinish) options.onFinish();
    },
  });
}

describe('Game show page', () => {
  let server;
  const baseGame = {
    id: 1,
    title: 'Sample Game',
    cover_url: null,
    summary: null,
    storyline: null,
    description: null,
    articles: [
      {
        id: 10,
        title: 'First review',
        slug: 'first-review',
        excerpt: 'A short article excerpt.',
        is_premium: false,
        published_at: null,
        reactions_count: 0,
        author: { name: null, username: 'editor' },
      },
    ],
    ratings: {
      enabled: true,
      average: null,
      count: 0,
      user: null,
    },
  };

  beforeEach(async () => {
    server = await createTestServer();
    vi.stubGlobal('route', (name) => name);
    globalThis.__INERTIA_PAGE__ = {
      props: { auth: { user: { id: 1, username: 'Alice', is_admin: false, is_editor: false } } },
      url: '/games/sample-game',
    };
  });

  afterEach(async () => {
    await server?.close();
    vi.restoreAllMocks();
    delete globalThis.__INERTIA_PAGE__;
  });

  it('renders game articles on the game page', async () => {
    const ratingForm = createMockForm({ rating: null });
    __setFormQueue([ratingForm]);

    const mod = await server.ssrLoadModule('/resources/js/pages/games/Show.vue');
    const component = mod.default;
    const { html } = await renderComponent(component, {
      game: baseGame,
      canCreateArticle: false,
      flash: null,
    });

    expect(html).toContain('Publications autour du jeu');
    expect(html).toContain('First review');
  });
});
