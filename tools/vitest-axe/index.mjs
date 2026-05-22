export function toHaveNoViolations(results) {
  const violations = results?.violations ?? [];
  const pass = violations.length === 0;
  return {
    pass,
    message: () =>
      pass
        ? 'Expected no accessibility violations and found none.'
        : `Expected no accessibility violations but found: ${violations
              .map((violation) => violation.id || violation.description)
              .join(', ')}`,
  };
}
