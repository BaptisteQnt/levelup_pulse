import { defineComponent, h } from 'vue';

export default defineComponent({
  name: 'AppLogoStub',
  setup() {
    return () => h('span', { 'data-logo': 'stub' }, 'Logo');
  },
});
