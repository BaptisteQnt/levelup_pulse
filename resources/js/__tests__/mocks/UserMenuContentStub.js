import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'UserMenuContentStub',
  props: {
    user: { type: Object, default: null },
  },
  setup(props) {
    return () => h('div', { 'data-user-menu': props.user ? 'present' : 'empty' });
  },
});
