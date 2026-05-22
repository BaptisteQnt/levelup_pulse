import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { reactive } from 'vue';
import { createTestServer } from './helpers/createServer.js';
import { renderComponent } from './helpers/renderComponent.js';
import { __setFormQueue } from './mocks/inertia.js';

function createMockForm(initial) {
  const state = reactive({
    ...initial,
    errors: {},
    processing: false,
    wasReset: false,
    __submissions: [],
    post(url, options = {}) {
      state.__submissions.push({ method: 'post', url, options });
      if (options.onSuccess) options.onSuccess();
      if (options.onFinish) options.onFinish();
    },
    reset(...fields) {
      state.wasReset = true;
      if (!fields.length) {
        Object.assign(state, initial);
        return;
      }
      fields.forEach((field) => {
        if (field in initial) {
          state[field] = initial[field];
        }
      });
    },
  });
  return state;
}

describe('Game show page forms', () => {
  let server;
  const baseGame = {
    id: 1,
    title: 'Sample Game',
    cover_url: null,
    summary: null,
    storyline: null,
    description: null,
    comments: [],
    tips: [],
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
      props: { auth: { user: { id: 1, username: 'Alice', is_admin: false } } },
      url: '/games/1',
    };
  });

  afterEach(async () => {
    await server?.close();
    vi.restoreAllMocks();
    delete globalThis.__INERTIA_PAGE__;
  });

  it('submits a new comment and resets the form', async () => {
    const commentForm = createMockForm({ content: '', game_id: 1 });
    const tipForm = createMockForm({ content: '', game_id: 1 });
    const ratingForm = createMockForm({ rating: null });
    __setFormQueue([commentForm, tipForm, ratingForm]);

    const mod = await server.ssrLoadModule('/resources/js/pages/games/Show.vue');
    const component = mod.default;
    const { instance } = await renderComponent(component, { game: baseGame, flash: null });

    const form = instance.setupState.form;
    form.content = 'Great gameplay!';

    await instance.setupState.submit();

    expect(commentForm.__submissions).toHaveLength(1);
    expect(commentForm.__submissions[0].url).toBe('comments.store');
    expect(commentForm.wasReset).toBeTruthy();
    expect(form.content).toBe('');
  });

  it('submits a new tip and resets the form', async () => {
    const commentForm = createMockForm({ content: '', game_id: 1 });
    const tipForm = createMockForm({ content: '', game_id: 1 });
    const ratingForm = createMockForm({ rating: null });
    __setFormQueue([commentForm, tipForm, ratingForm]);

    const mod = await server.ssrLoadModule('/resources/js/pages/games/Show.vue');
    const component = mod.default;
    const { instance } = await renderComponent(component, { game: baseGame, flash: null });

    const tipFormState = instance.setupState.tipForm;
    tipFormState.content = 'Collect resources early!';

    await instance.setupState.submitTip();

    expect(tipForm.__submissions).toHaveLength(1);
    expect(tipForm.__submissions[0].url).toBe('tips.store');
    expect(tipForm.wasReset).toBeTruthy();
    expect(tipFormState.content).toBe('');
  });
});
