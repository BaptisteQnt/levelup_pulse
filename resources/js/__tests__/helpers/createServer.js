import vue from '@vitejs/plugin-vue';
import { createServer } from 'vite';
import path from 'node:path';

const root = path.resolve('resources/js');
const mocksRoot = path.resolve('resources/js/__tests__/mocks');

const baseAliases = [
  { find: '@inertiajs/vue3', replacement: path.join(mocksRoot, 'inertia.js') },
  { find: '@/layouts/auth/AuthSimpleLayout.vue', replacement: path.join(mocksRoot, 'AuthSimpleLayoutStub.js') },
  { find: '@/layouts/auth/AuthSimpleLayout', replacement: path.join(mocksRoot, 'AuthSimpleLayoutStub.js') },
  { find: '@/layouts/AuthLayout.vue', replacement: path.join(mocksRoot, 'AuthLayoutStub.js') },
  { find: '@/layouts/AuthLayout', replacement: path.join(mocksRoot, 'AuthLayoutStub.js') },
  { find: '@/layouts/app/AppHeaderLayout.vue', replacement: path.join(mocksRoot, 'AppHeaderLayoutStub.js') },
  { find: '@/layouts/app/AppHeaderLayout', replacement: path.join(mocksRoot, 'AppHeaderLayoutStub.js') },
  { find: '@/components/AppHeader.vue', replacement: path.join(mocksRoot, 'AppHeaderSimpleStub.js') },
  { find: '@/components/AppHeader', replacement: path.join(mocksRoot, 'AppHeaderSimpleStub.js') },
  { find: '@/components/InputError.vue', replacement: path.join(mocksRoot, 'InputErrorStub.js') },
  { find: '@/components/TextLink.vue', replacement: path.join(mocksRoot, 'TextLinkStub.js') },
  { find: '@/components/AppLogo.vue', replacement: path.join(mocksRoot, 'AppLogoStub.js') },
  { find: '@/components/Breadcrumbs.vue', replacement: path.join(mocksRoot, 'BreadcrumbsStub.js') },
  { find: '@/components/UserMenuContent.vue', replacement: path.join(mocksRoot, 'UserMenuContentStub.js') },
  { find: '@/components/ui/input', replacement: path.join(mocksRoot, 'InputStub.js') },
  { find: '@/components/ui/checkbox', replacement: path.join(mocksRoot, 'CheckboxStub.js') },
  { find: '@/components/ui/button', replacement: path.join(mocksRoot, 'ButtonStub.js') },
  { find: '@/components/ui/label', replacement: path.join(mocksRoot, 'LabelStub.js') },
  { find: '@/components/ui/avatar', replacement: path.join(mocksRoot, 'AvatarStub.js') },
  { find: '@/components/ui/dropdown-menu', replacement: path.join(mocksRoot, 'DropdownMenuStub.js') },
  { find: '@/composables/useInitials', replacement: path.join(mocksRoot, 'useInitialsStub.js') },
  { find: '@/composables/useActiveLink', replacement: path.join(mocksRoot, 'useActiveLinkStub.js') },
  { find: '@/lib/premium', replacement: path.join(mocksRoot, 'premium.js') },
  { find: 'lucide-vue-next', replacement: path.join(mocksRoot, 'LoaderCircleStub.js') },
  { find: '@', replacement: root },
];

export async function createTestServer(extraAliases = {}) {
  const alias = [...baseAliases];
  for (const [find, replacement] of Object.entries(extraAliases)) {
    alias.push({ find, replacement });
  }
  return createServer({
    configFile: false,
    server: { middlewareMode: true },
    plugins: [vue()],
    resolve: {
      alias,
    },
  });
}
