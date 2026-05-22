import { defineComponent, h, reactive } from 'vue';

let formQueue = globalThis.__INERTIA_FORM_QUEUE__ ?? [];

export const __setFormQueue = (queue) => {
  formQueue = queue.slice();
  globalThis.__INERTIA_FORM_QUEUE__ = formQueue;
};

const createForm = (initial) => {
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
      for (const field of fields) {
        if (field in initial) {
          state[field] = initial[field];
        }
      }
    },
  });
  return state;
};

export const useForm = (initial) => {
  const queue = globalThis.__INERTIA_FORM_QUEUE__ || formQueue;
  if (queue.length) {
    const next = queue.shift();
    formQueue = queue;
    globalThis.__INERTIA_FORM_QUEUE__ = queue;
    return next;
  }
  return createForm(initial);
};

export const Head = defineComponent({
  name: 'HeadStub',
  props: { title: String },
  setup(_, { slots }) {
    return () => (slots.default ? slots.default() : null);
  },
});

export const Link = defineComponent({
  name: 'InertiaLinkStub',
  props: {
    href: { type: String, default: '#' },
    method: { type: String, default: 'get' },
    as: { type: String, default: 'a' },
    type: { type: String, default: 'button' },
  },
  setup(props, { slots, attrs }) {
    const tag = props.as === 'button' ? 'button' : 'a';
    const finalAttrs = { ...attrs };
    if (tag === 'a') {
      finalAttrs.href = props.href;
    } else {
      finalAttrs.type = props.type;
      finalAttrs['data-method'] = props.method;
    }
    return () => h(tag, finalAttrs, slots.default ? slots.default() : []);
  },
});

export const router = {
  lastPost: null,
  lastDelete: null,
  post(url, data = {}, options = {}) {
    this.lastPost = { url, data, options };
    if (options.onFinish) options.onFinish();
  },
  delete(url, options = {}) {
    this.lastDelete = { url, options };
    if (options.onFinish) options.onFinish();
  },
};

export const usePage = () => globalThis.__INERTIA_PAGE__ ?? { props: { auth: { user: null } }, url: '/' };
