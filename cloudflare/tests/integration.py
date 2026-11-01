"""Run against `npm run dev -- --port 8788`; uses local D1 only.

Creates labelled test accounts/content; never connects to a remote database.
"""
import http.cookiejar
import json
import subprocess
import time
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = 'http://localhost:8788'
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(path, body=None, expected=200, origin=BASE):
    data = None if body is None else json.dumps(body).encode()
    req = urllib.request.Request(BASE + '/api/' + path, data=data,
                                 headers={'Origin': origin, 'Content-Type': 'application/json'})
    try:
        response = client.open(req)
    except urllib.error.HTTPError as error:
        response = error
    result = json.loads(response.read())
    assert response.code == expected, (path, response.code, result)
    return result


def sql(command):
    subprocess.run(['npx', 'wrangler', 'd1', 'execute', 'ursors', '--local', '--command', command],
                   cwd=ROOT, check=True, stdout=subprocess.DEVNULL)


username = 'cf_test_' + str(int(time.time()))
password = ' test password preserves spaces '
account = dict(username=username, email=username + '@example.invalid', name='Cloudflare Test', role='student', password=password)
request('me', expected=401)
request('register', {**account, 'role': 'admin'}, expected=400)
request('register', account, expected=201)
credentials = dict(username=username, password=password)
request('login', credentials, expected=401)
sql(f"UPDATE users SET approved=1 WHERE username='{username}'")
request('login', credentials)
assert request('me')['user']['role'] == 'student'
request('admin/users', expected=403)
catalogue = json.loads((ROOT / 'public/catalogue.json').read_text())
category = next(iter(catalogue))
post = dict(content='Local integration resource', resource_url='https://example.org/resource',
            category=category, subject=catalogue[category][0], audience='general')
request('posts', post, expected=403, origin='https://evil.example')
request('posts', {**post, 'audience': 'professor'}, expected=403)
request('posts', {**post, 'resource_url': 'javascript:alert(1)'}, expected=400)
request('posts', post, expected=201)
post_id = request('posts')['posts'][0]['id']
request(f'posts/{post_id}/like', {'liked': True})
request(f'posts/{post_id}/like', {'liked': True})
assert request('posts')['posts'][0]['likes'] == 1
request(f'posts/{post_id}/comments', {'content': '<script>text only</script>'}, expected=201)
assert request(f'posts/{post_id}/comments')['comments'][0]['content'] == '<script>text only</script>'
request(f'posts/{post_id}/archive', {}, expected=403)
sql(f"UPDATE users SET can_post=0 WHERE username='{username}'")
request('posts', post, expected=403)
sql(f"UPDATE users SET approved=0 WHERE username='{username}'")
request('me', expected=401)
sql(f"DELETE FROM sessions WHERE user_id IN (SELECT id FROM users WHERE username='{username}')")
print('PASS: pending login, roles, CSRF, link validation, posts, idempotent likes, comments, posting restriction and approval revocation.')
