import { test } from 'node:test';
import assert from 'node:assert/strict';
import { pbkdf2Sync } from 'node:crypto';
import { resourceLink, validateAccount, hashPassword, verifyPassword, checkOrigin } from '../functions/lib/security.js';
test('resource links reject executable schemes and embedded credentials', () => {
  for (const value of ['javascript:alert(1)', 'http://example.org', 'https://user:pass@example.org', 'not a url']) assert.throws(() => resourceLink(value));
  assert.equal(resourceLink('https://example.org/file'), 'https://example.org/file');
  assert.equal(resourceLink(''), null);
});
test('public registration cannot create administrators', () => {
  const body = { username: 'student1', name: 'Sample User', email: 'sample@example.org', password: 'long password here', role: 'student' };
  assert.equal(validateAccount(body).role, 'student');
  assert.throws(() => validateAccount({ ...body, role: 'admin' }));
  assert.throws(() => validateAccount({ ...body, password: 'short' }));
});
test('mutations require same origin and JSON', () => {
  const request = headers => new Request('https://ursors.pages.dev/api/posts', { method: 'POST', headers });
  assert.throws(() => checkOrigin(request({ Origin: 'https://evil.example', 'Content-Type': 'application/json' })));
  assert.throws(() => checkOrigin(request({ Origin: 'https://ursors.pages.dev', 'Content-Type': 'text/plain' })));
  checkOrigin(request({ Origin: 'https://ursors.pages.dev', 'Content-Type': 'application/json' }));
});
test('password hashing uses random salts and verifies correctly', async () => {
  const first = await hashPassword('correct horse battery staple');
  const second = await hashPassword('correct horse battery staple');
  assert.match(first, /^scrypt\$32768\$8\$3\$[a-f0-9]{64}\$[a-f0-9]{64}$/);
  assert.notEqual(first, second);
  assert.equal(await verifyPassword('correct horse battery staple', first), true);
  assert.equal(await verifyPassword('incorrect', first), false);
});
test('rejects unapproved scrypt costs and preserves local legacy password checks', async () => {
  assert.equal(await verifyPassword('test', `scrypt$2$8$3$${'0'.repeat(64)}$${'0'.repeat(64)}`), false);
  const salt='01'.repeat(32), password='legacy local preview';
  const hash=pbkdf2Sync(password,Buffer.from(salt,'hex'),600000,32,'sha256').toString('hex');
  assert.equal(await verifyPassword(password,`pbkdf2$600000$${salt}$${hash}`),true);
});
