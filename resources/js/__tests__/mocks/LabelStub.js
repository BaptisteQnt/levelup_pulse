import { defineComponent, h } from 'vue';

export const Label = defineComponent({
  name: 'LabelStub',
  setup(_, { slots, attrs }) {
    return () => h('label', attrs, slots.default ? slots.default() : []);
  },
});

export default Label;
