function extractHtml(node) {
  if (!node) {
    return '';
  }
  if (typeof node === 'string') {
    return node;
  }
  if (node.outerHTML) {
    return node.outerHTML;
  }
  if (node.innerHTML) {
    return node.innerHTML;
  }
  return '';
}

export async function run(context) {
  const html = extractHtml(context);
  const violations = [];
  const navMatches = [...html.matchAll(/<nav\b[^>]*>/gi)];
  if (navMatches.length) {
    navMatches.forEach((match) => {
      const tag = match[0];
      const hasAriaLabel = /aria-label\s*=\s*"[^"]+"/i.test(tag) || /aria-labelledby\s*=\s*"[^"]+"/i.test(tag);
      const hasRole = /role\s*=\s*"navigation"/i.test(tag);
      if (!hasAriaLabel && !hasRole) {
        violations.push({
          id: 'navigation-aria-label',
          impact: 'moderate',
          description: 'Navigation landmark should provide an accessible name.',
        });
      }
    });
  }
  return { violations };
}
