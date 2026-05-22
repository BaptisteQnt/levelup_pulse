export const useActiveLink = () => ({
  isActive: (href) => href === globalThis.__ACTIVE_LINK__ || href === '/',
});
