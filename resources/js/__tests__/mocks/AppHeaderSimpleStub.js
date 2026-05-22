import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'AppHeaderSimpleStub',
  props: {
    breadcrumbs: { type: Array, default: () => [] },
  },
  setup(props, { slots }) {
    return () =>
      h('header', { 'data-app-header': 'stub', breadcrumbs: JSON.stringify(props.breadcrumbs) }, slots.default ? slots.default() : []);
  },
});
