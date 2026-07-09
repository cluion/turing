// Keeps the wire-contract docs honest: every JSON block in wire-contract.md that
// is tagged with `<!-- vector:NAME.json -->` must be structurally equal to the
// real fixture in php/tests/vectors/NAME.json. Run in docs:build so the site
// cannot ship a spec that has drifted from the tests. Exits non-zero on drift.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const docPath = fileURLToPath(new URL('../reference/wire-contract.md', import.meta.url));
const vectorsDir = fileURLToPath(new URL('../../php/tests/vectors/', import.meta.url));

/** Canonical stringify (recursively sorted keys) so equality ignores formatting. */
function canonical(value) {
  if (Array.isArray(value)) return `[${value.map(canonical).join(',')}]`;
  if (value && typeof value === 'object') {
    return `{${Object.keys(value)
      .sort()
      .map((k) => `${JSON.stringify(k)}:${canonical(value[k])}`)
      .join(',')}}`;
  }
  return JSON.stringify(value);
}

const doc = readFileSync(docPath, 'utf8');
const tag = /<!--\s*vector:([\w.-]+)\s*-->\s*```json\n([\s\S]*?)\n```/g;

let checked = 0;
const failures = [];
for (const [, name, block] of doc.matchAll(tag)) {
  checked++;
  let embedded;
  let actual;
  try {
    embedded = JSON.parse(block);
  } catch (error) {
    failures.push(`${name}: embedded JSON does not parse — ${error.message}`);
    continue;
  }
  try {
    actual = JSON.parse(readFileSync(vectorsDir + name, 'utf8'));
  } catch (error) {
    failures.push(`${name}: cannot read fixture — ${error.message}`);
    continue;
  }
  if (canonical(embedded) !== canonical(actual)) {
    failures.push(`${name}: embedded JSON has drifted from php/tests/vectors/${name}`);
  }
}

if (checked === 0) {
  console.error('check-vectors: no <!-- vector:NAME --> blocks found; the guard would pass vacuously.');
  process.exit(1);
}
if (failures.length > 0) {
  console.error('check-vectors: FAILED\n' + failures.map((f) => '  - ' + f).join('\n'));
  process.exit(1);
}
console.log(`check-vectors: OK (${checked} vector blocks match the fixtures)`);
