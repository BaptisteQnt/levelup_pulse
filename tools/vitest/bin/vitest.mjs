#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { runTests } from '../index.mjs';

const args = process.argv.slice(2).filter((arg) => !arg.startsWith('--'));
const targetPatterns = args.length ? args : ['resources/js/__tests__'];

async function collectTestFiles(target) {
  const stat = await fs.stat(target);
  if (stat.isDirectory()) {
    const entries = await fs.readdir(target, { withFileTypes: true });
    const files = [];
    for (const entry of entries) {
      const fullPath = path.join(target, entry.name);
      if (entry.isDirectory()) {
        files.push(...(await collectTestFiles(fullPath)));
      } else if (/\.(test|spec)\.(cjs|mjs|js|ts)$/.test(entry.name)) {
        files.push(fullPath);
      }
    }
    return files;
  }
  if (/\.(test|spec)\.(cjs|mjs|js|ts)$/.test(path.basename(target))) {
    return [target];
  }
  return [];
}

let files = [];
for (const pattern of targetPatterns) {
  files.push(...(await collectTestFiles(pattern)));
}

files = Array.from(new Set(files));

for (const file of files) {
  await import(pathToFileURL(file));
}

const results = await runTests();

for (const log of results.logs) {
  if (log.type === 'success') {
    console.log(`✓ ${log.path.join(' › ')}`);
  }
}

if (results.failures.length) {
  console.error(`\n${results.failures.length} test(s) failed.`);
  for (const failure of results.failures) {
    console.error(`✗ ${failure.path.join(' › ')}`);
    console.error(failure.error.stack ?? failure.error.message);
  }
  process.exit(1);
}

console.log(`\nTest Suites: ${results.suites}, Passed: ${results.testsPassed}, Failed: ${results.testsFailed}`);
