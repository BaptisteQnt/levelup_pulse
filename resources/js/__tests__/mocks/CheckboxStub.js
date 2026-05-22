import { defineComponent, h } from 'vue';

export const Checkbox = defineComponent({
  name: 'CheckboxStub',
  props: {
    modelValue: { type: Boolean, default: false },
  },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () =>
      h('input', {
        ...attrs,
        type: 'checkbox',
        checked: props.modelValue,
        onChange: (event) => emit('update:modelValue', event.target.checked),
      });
  },
});

export default Checkbox;
