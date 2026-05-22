export const useInitials = () => ({
  getInitials: (name = '') =>
    name
      .split(/\s+/)
      .filter(Boolean)
      .map((part) => part[0]?.toUpperCase() ?? '')
      .join('') || 'NA',
});
