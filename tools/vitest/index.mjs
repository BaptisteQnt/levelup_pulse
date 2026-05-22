import assert from 'node:assert/strict';

class Suite {
  constructor(name, parent = null) {
    this.name = name;
    this.parent = parent;
    this.suites = [];
    this.tests = [];
    this.beforeEach = [];
    this.afterEach = [];
  }
}

const rootSuite = new Suite('');
let currentSuite = rootSuite;
const customMatchers = {};
const globalStubs = [];

export function describe(name, fn) {
  const parent = currentSuite;
  const suite = new Suite(name, parent);
  parent.suites.push(suite);
  currentSuite = suite;
  try {
    fn();
  } finally {
    currentSuite = parent;
  }
}

export const suite = describe;

export function it(name, fn) {
  currentSuite.tests.push({ name, fn });
}

export const test = it;

export function beforeEach(fn) {
  currentSuite.beforeEach.push(fn);
}

export function afterEach(fn) {
  currentSuite.afterEach.push(fn);
}

function matcherCollection() {
  return {
    ...customMatchers,
    toBe(received, expected) {
      assert.strictEqual(received, expected);
    },
    toEqual(received, expected) {
      assert.deepStrictEqual(received, expected);
    },
    toStrictEqual(received, expected) {
      assert.deepStrictEqual(received, expected);
    },
    toBeTruthy(received) {
      assert.ok(received);
    },
    toBeFalsy(received) {
      assert.ok(!received);
    },
    toContain(received, expected) {
      assert.ok(received.includes(expected));
    },
    toHaveLength(received, expected) {
      assert.strictEqual(received.length, expected);
    },
    toBeCalled(mock) {
      assert.ok(mock.mock && mock.mock.calls.length > 0);
    },
    toHaveBeenCalled(mock) {
      assert.ok(mock.mock && mock.mock.calls.length > 0);
    },
    toHaveBeenCalledTimes(mock, expected) {
      assert.ok(mock.mock);
      assert.strictEqual(mock.mock.calls.length, expected);
    },
    toHaveBeenCalledWith(mock, ...args) {
      assert.ok(mock.mock);
      const found = mock.mock.calls.some((call) => call.length === args.length && call.every((value, index) => {
        try {
          assert.deepStrictEqual(value, args[index]);
          return true;
        } catch {
          return false;
        }
      }));
      assert.ok(found);
    },
    toBeDefined(received) {
      assert.notStrictEqual(received, undefined);
    },
    toBeUndefined(received) {
      assert.strictEqual(received, undefined);
    },
  };
}

class Expectation {
  constructor(value, isNot = false) {
    this.value = value;
    this.isNot = isNot;
  }

  get not() {
    return new Expectation(this.value, !this.isNot);
  }
}

function executeMatcher(instance, name, args) {
  const matchers = matcherCollection();
  const matcher = matchers[name];
  if (!matcher) {
    throw new Error(`Matcher ${name} is not defined`);
  }
  try {
    const result = matcher.call(instance, instance.value, ...args);
    if (result && typeof result === 'object' && 'pass' in result) {
      const pass = instance.isNot ? !result.pass : result.pass;
      if (!pass) {
        const message = result.message ? result.message(instance.isNot) : `Expectation failed: ${name}`;
        throw new Error(typeof message === 'function' ? message() : message);
      }
      return;
    }
    if (typeof result === 'boolean') {
      const pass = instance.isNot ? !result : result;
      if (!pass) {
        throw new Error(`Expectation failed: ${instance.isNot ? 'not ' : ''}${name}`);
      }
    }
  } catch (error) {
    if (!instance.isNot) {
      throw error;
    }
    throw error;
  }
}

const expectationHandler = {
  get(target, prop) {
    if (prop === 'not') {
      return createExpectation(target.value, !target.isNot);
    }
    const matchers = matcherCollection();
    if (prop in target) {
      return target[prop];
    }
    if (typeof matchers[prop] === 'function') {
      return (...args) => executeMatcher(target, prop, args);
    }
    return undefined;
  },
};

function createExpectation(value, isNot = false) {
  const instance = new Expectation(value, isNot);
  return new Proxy(instance, expectationHandler);
}

export function expect(value) {
  return createExpectation(value);
}

expect.extend = (matchers) => {
  Object.assign(customMatchers, matchers);
};

const vi = {
  fn(impl = () => undefined) {
    const mockFn = function (...args) {
      mockFn.mock.calls.push(args);
      return impl.apply(this, args);
    };
    mockFn.mockImplementation = (newImpl) => {
      impl = newImpl;
      return mockFn;
    };
    mockFn.mockClear = () => {
      mockFn.mock.calls.length = 0;
      return mockFn;
    };
    mockFn.mockReturnValue = (value) => {
      impl = () => value;
      return mockFn;
    };
    mockFn.mock = { calls: [] };
    return mockFn;
  },
  stubGlobal(key, value) {
    const hadOwn = Object.prototype.hasOwnProperty.call(globalThis, key);
    const original = globalThis[key];
    globalThis[key] = value;
    globalStubs.push({ key, original, hadOwn });
  },
  restoreAllMocks() {
    while (globalStubs.length) {
      const { key, original, hadOwn } = globalStubs.pop();
      if (hadOwn) {
        globalThis[key] = original;
      } else {
        delete globalThis[key];
      }
    }
  },
};

export { vi };

async function runSuite(suite, ancestors, results) {
  const currentPath = [...ancestors.map((s) => s.name), suite.name].filter(Boolean);
  for (const child of suite.suites) {
    await runSuite(child, [...ancestors, suite], results);
  }

  for (const testCase of suite.tests) {
    const hookBefores = [...ancestors, suite].flatMap((s) => s.beforeEach);
    const hookAfters = [...[suite, ...ancestors].flatMap((s) => s.afterEach)].reverse();

    try {
      for (const hook of hookBefores) {
        await hook();
      }
      await testCase.fn();
      results.testsPassed += 1;
      results.logs.push({ type: 'success', path: [...currentPath, testCase.name] });
    } catch (error) {
      results.testsFailed += 1;
      results.failures.push({ path: [...currentPath, testCase.name], error });
    } finally {
      try {
        for (const hook of hookAfters) {
          await hook();
        }
      } finally {
        vi.restoreAllMocks();
      }
    }
  }

  if (suite.tests.length || suite.suites.length) {
    results.suites += 1;
  }
}

export async function runTests() {
  const results = {
    suites: 0,
    testsPassed: 0,
    testsFailed: 0,
    failures: [],
    logs: [],
  };

  await runSuite(rootSuite, [], results);
  return results;
}

export function resetSuites() {
  rootSuite.suites.length = 0;
  rootSuite.tests.length = 0;
  rootSuite.beforeEach.length = 0;
  rootSuite.afterEach.length = 0;
  Object.keys(customMatchers).forEach((key) => delete customMatchers[key]);
}
