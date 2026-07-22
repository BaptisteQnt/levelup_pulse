import { defineComponent, h } from 'vue';

export const LoaderCircle = defineComponent({
  name: 'LoaderCircleStub',
  setup(_, { attrs }) {
    return () => h('span', { ...attrs, 'data-loader': 'stub' });
  },
});

const IconStub = defineComponent({
  name: 'IconStub',
  setup(_, { attrs }) {
    return () => h('span', { ...attrs, 'data-icon': 'stub', 'aria-hidden': attrs['aria-hidden'] ?? 'true' });
  },
});

export const ArrowRight = IconStub;
export const BookOpen = IconStub;
export const Check = IconStub;
export const Crown = IconStub;
export const ExternalLink = IconStub;
export const Gamepad2 = IconStub;
export const Lightbulb = IconStub;
export const Megaphone = IconStub;
export const Newspaper = IconStub;
export const RotateCcw = IconStub;
export const Search = IconStub;
export const ShieldCheck = IconStub;
export const Sparkles = IconStub;
export const Star = IconStub;
export const UserMinus = IconStub;
export const Users = IconStub;
export const WandSparkles = IconStub;
export const X = IconStub;

export default {
  LoaderCircle,
  ArrowRight,
  BookOpen,
  Check,
  Crown,
  ExternalLink,
  Gamepad2,
  Lightbulb,
  Megaphone,
  Newspaper,
  RotateCcw,
  Search,
  ShieldCheck,
  Sparkles,
  Star,
  UserMinus,
  Users,
  WandSparkles,
  X,
};
