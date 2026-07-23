import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createTestServer } from './helpers/createServer.js';
import { renderComponent } from './helpers/renderComponent.js';

describe('Article editor AI assistants', () => {
  let server;

  beforeEach(async () => {
    server = await createTestServer();
    vi.stubGlobal('route', (name) => name);
    globalThis.__INERTIA_PAGE__ = {
      props: { auth: { user: { id: 1, is_editor: true } } },
      url: '/games/test-game/articles/create',
    };
  });

  afterEach(async () => {
    await server?.close();
    vi.restoreAllMocks();
    delete globalThis.__INERTIA_PAGE__;
  });

  it('renders trend discovery and correction controls with human approval messaging', async () => {
    const mod = await server.ssrLoadModule('/resources/js/pages/articles/Edit.vue');
    const { html } = await renderComponent(mod.default, {
      mode: 'create',
      game: { id: 1, title: 'Test Game', slug: 'test-game', cover_url: null },
      article: null,
    });

    expect(html).toContain('Suggérer 5 jeux tendance');
    expect(html).toContain('Corriger mon texte');
    expect(html).toContain('L’IA propose, vous décidez');
    expect(html).toContain('n’est ni appliquée automatiquement ni enregistrée');
  });
});
