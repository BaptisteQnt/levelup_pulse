import { defineComponent, h } from 'vue';

export const Button = defineComponent({
  name: 'ButtonStub',
  setup(_, { slots, attrs }) {
    return () => h('button', { type: attrs.type ?? 'button', ...attrs }, slots.default ? slots.default() : []);
  },
});

export default Button;
