import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { run } from 'axe-core';
import { toHaveNoViolations } from 'vitest-axe';
import { createTestServer } from './helpers/createServer.js';
import { renderComponent } from './helpers/renderComponent.js';
import path from 'node:path';

expect.extend({ toHaveNoViolations });

describe('Main navigation accessibility', () => {
  let server;

  beforeEach(async () => {
    const headerPath = path.resolve('resources/js/components/AppHeader.vue');
    server = await createTestServer({ '@/components/AppHeader.vue': headerPath, '@/components/AppHeader': headerPath });
    vi.stubGlobal('route', (name) => name);
    globalThis.__INERTIA_PAGE__ = {
      props: { auth: { user: null } },
      url: '/',
    };
    globalThis.__ACTIVE_LINK__ = '/';
  });

  afterEach(async () => {
    await server?.close();
    vi.restoreAllMocks();
    delete globalThis.__INERTIA_PAGE__;
    delete globalThis.__ACTIVE_LINK__;
  });

  it('renders navigation without accessibility violations', async () => {
    const mod = await server.ssrLoadModule('/resources/js/components/AppHeader.vue');
    const component = mod.default;
    const { html } = await renderComponent(component, { breadcrumbs: [] });

    const results = await run(html);
    expect(results).toHaveNoViolations();
  });
});
