import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'AuthSimpleLayoutStub',
  props: {
    title: String,
    description: String,
  },
  setup(props, { slots }) {
    return () =>
      h(
        'section',
        {
          'data-auth-simple-layout': 'stub',
          title: props.title,
          description: props.description,
        },
        slots.default ? slots.default() : []
      );
  },
});
