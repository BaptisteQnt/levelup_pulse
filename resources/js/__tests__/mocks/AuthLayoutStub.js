import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'AuthLayoutStub',
  props: {
    title: String,
    description: String,
  },
  setup(props, { slots }) {
    return () =>
      h(
        'div',
        {
          'data-auth-layout': 'stub',
          title: props.title,
          description: props.description,
        },
        slots.default ? slots.default() : []
      );
  },
});
