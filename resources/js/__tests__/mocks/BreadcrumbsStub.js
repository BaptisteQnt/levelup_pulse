import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'BreadcrumbsStub',
  props: {
    breadcrumbs: { type: Array, default: () => [] },
  },
  setup(props) {
    return () => h('nav', { 'aria-label': 'Fil d\'Ariane', breadcrumbs: JSON.stringify(props.breadcrumbs) });
  },
});
