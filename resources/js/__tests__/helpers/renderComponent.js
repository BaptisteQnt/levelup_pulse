import { createSSRApp } from 'vue';
import { renderToString } from 'vue/server-renderer';

export async function renderComponent(component, props = {}, { provide = {} } = {}) {
  let instance = null;
  const app = createSSRApp(component, props);

  if (typeof globalThis.route === 'function') {
    app.config.globalProperties.route = globalThis.route;
  }

  Object.entries(provide).forEach(([key, value]) => {
    app.provide(key, value);
  });

  app.mixin({
    created() {
      if (!instance && this.$?.type?.__file === component.__file) {
        instance = this.$;
      }
    },
  });

  const html = await renderToString(app);

  if (!instance) {
    throw new Error('Failed to capture component instance');
  }

  return { instance, html };
}
