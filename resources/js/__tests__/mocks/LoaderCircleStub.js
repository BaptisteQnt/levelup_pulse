import { defineComponent, h } from 'vue';

export const LoaderCircle = defineComponent({
  name: 'LoaderCircleStub',
  setup(_, { attrs }) {
    return () => h('span', { ...attrs, 'data-loader': 'stub' });
  },
});

export default { LoaderCircle };
