import { defineComponent, h } from 'vue';

export const DropdownMenu = defineComponent({
  name: 'DropdownMenuStub',
  setup(_, { slots }) {
    return () => h('div', { 'data-dropdown': 'menu' }, slots.default ? slots.default() : []);
  },
});

export const DropdownMenuTrigger = defineComponent({
  name: 'DropdownMenuTriggerStub',
  setup(_, { slots, attrs }) {
    return () => h('button', { type: 'button', ...attrs, 'data-dropdown-trigger': 'stub' }, slots.default ? slots.default() : []);
  },
});

export const DropdownMenuContent = defineComponent({
  name: 'DropdownMenuContentStub',
  setup(_, { slots, attrs }) {
    return () => h('div', { ...attrs, 'data-dropdown-content': 'stub' }, slots.default ? slots.default() : []);
  },
});

export default { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent };
