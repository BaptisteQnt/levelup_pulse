import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'InputErrorStub',
  props: {
    message: { type: String, default: '' },
  },
  setup(props) {
    return () => (props.message ? h('p', { class: 'input-error-stub' }, props.message) : null);
  },
});
