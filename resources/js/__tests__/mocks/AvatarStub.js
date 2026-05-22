import { defineComponent, h } from 'vue';

export const Avatar = defineComponent({
  name: 'AvatarStub',
  setup(_, { slots, attrs }) {
    return () => h('div', { ...attrs, 'data-avatar': 'stub' }, slots.default ? slots.default() : []);
  },
});

export const AvatarImage = defineComponent({
  name: 'AvatarImageStub',
  props: { src: String, alt: String },
  setup(props, { attrs }) {
    return () => h('img', { ...attrs, src: props.src, alt: props.alt });
  },
});

export const AvatarFallback = defineComponent({
  name: 'AvatarFallbackStub',
  setup(_, { slots }) {
    return () => h('span', { 'data-avatar-fallback': 'stub' }, slots.default ? slots.default() : []);
  },
});

export default { Avatar, AvatarImage, AvatarFallback };
