export function fail(message, status = 400) {
  throw Object.assign(new Error(message), { status });
}
export function text(value, label, max, min = 1) {
  if (typeof value !== 'string') fail(`${label} is required.`);
  const result = value.trim();
  if (result.length < min || result.length > max) fail(`${label} must be ${min}–${max} characters.`);
  return result;
}
export function resourceLink(value) {
  if (!value) return null;
  const raw = text(value, 'Resource link', 2048);
  let url;
  try { url = new URL(raw); } catch { fail('Enter a complete HTTPS resource link.'); }
  if (url.protocol !== 'https:' || url.username || url.password) fail('Use an HTTPS link without embedded credentials.');
  return url.href;
}
export function validateAccount(body) {
  const username = text(body.username, 'Username', 50, 3);
  if (!/^[a-zA-Z0-9_]+$/.test(username)) fail('Username may contain letters, numbers and underscores only.');
  const email = text(body.email, 'Email', 100);
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) fail('Enter a valid email.');
  const name = text(body.name, 'Name', 150);
  if (!['student', 'teacher'].includes(body.role)) fail('Choose student or teacher.');
  if (typeof body.password !== 'string' || body.password.length < 12 || body.password.length > 128) fail('Password must be 12–128 characters.');
  return { username, email, name, role: body.role, password: body.password };
}
const hex = bytes => Array.from(new Uint8Array(bytes), b => b.toString(16).padStart(2, '0')).join('');
const unhex = str => Uint8Array.from(str.match(/../g), s => parseInt(s, 16));
export const randomToken = () => hex(crypto.getRandomValues(new Uint8Array(32)));
export async function digest(value) {
  return hex(await crypto.subtle.digest('SHA-256', new TextEncoder().encode(value)));
}
async function derive(password, salt, iterations) {
  const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(password), 'PBKDF2', false, ['deriveBits']);
  return hex(await crypto.subtle.deriveBits({ name: 'PBKDF2', hash: 'SHA-256', salt: unhex(salt), iterations }, key, 256));
}
export async function hashPassword(password) {
  const salt = randomToken();
  return `pbkdf2$600000$${salt}$${await derive(password, salt, 600000)}`;
}
export async function verifyPassword(password, encoded) {
  const [algorithm, count, salt, expected] = encoded.split('$');
  if (algorithm !== 'pbkdf2' || count !== '600000' || !/^[a-f0-9]{64}$/.test(salt) || !/^[a-f0-9]{64}$/.test(expected)) return false;
  const actual = await derive(password, salt, Number(count));
  let difference = 0;
  for (let i = 0; i < actual.length; i++) difference |= actual.charCodeAt(i) ^ expected.charCodeAt(i);
  return difference === 0;
}
export function checkOrigin(request) {
  if (request.headers.get('Origin') !== new URL(request.url).origin) fail('Refresh the page and try again.', 403);
  if (!(request.headers.get('Content-Type') || '').startsWith('application/json')) fail('JSON required.', 415);
}
