import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { createTestServer } from './helpers/createServer.js';
import { renderComponent } from './helpers/renderComponent.js';
import { __setFormQueue } from './mocks/inertia.js';
import { reactive } from 'vue';

function createMockForm(initial) {
  const state = reactive({
    ...initial,
    errors: {},
    processing: false,
    wasReset: false,
    __submissions: [],
    post(url, options = {}) {
      state.__submissions.push({ url, options });
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

describe('Login form', () => {
  let server;

  beforeEach(async () => {
    server = await createTestServer();
    vi.stubGlobal('route', (name) => name);
  });

  afterEach(async () => {
    await server?.close();
    vi.restoreAllMocks();
  });

  it('submits the login form and resets password field', async () => {
    const forms = [createMockForm({ email: '', password: '', remember: false })];
    __setFormQueue(forms);

    const mod = await server.ssrLoadModule('/resources/js/pages/auth/Login.vue');
    const component = mod.default;

    const { instance } = await renderComponent(component, { canResetPassword: true });

    const form = instance.setupState.form;
    form.email = 'user@example.com';
    form.password = 'secret';
    form.remember = true;

    await instance.setupState.submit();

    expect(form.__submissions).toHaveLength(1);
    expect(form.__submissions[0].url).toBe('login');
    expect(form.wasReset).toBeTruthy();
    expect(form.password).toBe('');
  });

  it('renders status message when provided', async () => {
    const mod = await server.ssrLoadModule('/resources/js/pages/auth/Login.vue');
    const component = mod.default;
    const { html } = await renderComponent(component, {
      canResetPassword: true,
      status: 'Password reset link sent!',
    });

    expect(html).toContain('Password reset link sent!');
  });
});
