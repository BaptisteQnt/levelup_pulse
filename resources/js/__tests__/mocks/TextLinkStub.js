import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'TextLinkStub',
  props: {
    href: { type: String, default: '#' },
  },
  setup(props, { slots, attrs }) {
    return () =>
      h(
        'a',
        {
          ...attrs,
          href: props.href,
        },
        slots.default ? slots.default() : []
      );
  },
});
