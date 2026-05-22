import { defineComponent, h } from 'vue';

export const Input = defineComponent({
  name: 'InputStub',
  props: {
    modelValue: [String, Number],
  },
  emits: ['update:modelValue'],
  setup(props, { emit, attrs }) {
    return () =>
      h('input', {
        ...attrs,
        value: props.modelValue ?? '',
        onInput: (event) => emit('update:modelValue', event.target.value),
      });
  },
});

export default Input;
