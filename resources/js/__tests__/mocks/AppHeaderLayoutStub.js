import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'AppHeaderLayoutStub',
  props: {
    breadcrumbs: { type: Array, default: () => [] },
  },
  setup(props, { slots }) {
    return () =>
      h('main', { 'data-app-header-layout': 'stub', breadcrumbs: JSON.stringify(props.breadcrumbs) }, slots.default ? slots.default() : []);
  },
});
