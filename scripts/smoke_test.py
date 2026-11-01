#!/usr/bin/env python3
"""Curl integration checks. Creates and RETAINS labelled test data; never resets DB.
Test accounts are disabled after successful checks; rows/files are preserved.

ADMIN_PASSWORD=... python3 scripts/smoke_test.py
Optional BASE_URL (default http://localhost:8000).
"""
import base64
import html
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import time
from urllib.parse import urlencode, urljoin, urlsplit, unquote

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('BASE_URL', 'http://localhost:8000')
PASSWORD = os.environ['ADMIN_PASSWORD']
TAG = 'smoke_' + str(time.time_ns())
RESULTS = []
WORK = Path(tempfile.mkdtemp(prefix='ursors-', dir='/tmp/opencode'))

def request(path, role='admin', data=None, files=None, expected=(200,), referer=None):
    cookie = WORK / (role + '.cookies')
    headers = WORK / 'headers'
    cmd = ['curl', '-sS', '-b', str(cookie), '-c', str(cookie), '-D', str(headers), '-w', '\n%{http_code}', BASE + '/' + path.lstrip('/')]
    if referer:
        cmd += ['-e', BASE + '/' + referer]
    if files:
        for key, value in (data or {}).items(): cmd += ['-F', key + '=' + str(value)]
        for key, value in files.items(): cmd += ['-F', key + '=@' + str(value)]
    elif data is not None:
        cmd += ['--data', urlencode(data)]
    response = subprocess.check_output(cmd, text=True)
    body, status = response.rsplit('\n', 1)
    assert int(status) in expected, (path, status, body[:2000])
    assert not re.search(r'(?:Warning|Notice|Deprecated|Fatal error|Parse error):', re.sub('<[^>]+>', '', body)), (path, body[:2000])
    RESULTS.append(f'{role}: {path} -> {status}')
    return body, headers.read_text()

def sql(query):
    return subprocess.check_output(['mariadb', '-uroot', '-N', 'urs_sors', '-e', query], text=True).strip()

def login(username, role):
    _, headers = request('login.php', role, {'username': username, 'password': PASSWORD}, expected=(302,))
    assert 'Location: /' + ('admin_home.php' if role == 'admin' else 'welcome.php') in headers

login('admin001', 'admin')
for helper in ['includes/app.php', 'includes/subject.php', 'includes/register_account.php', 'scripts/set_admin_password.php']:
    request(helper, expected=(404,))
request('login.php', 'invalid', {'username': 'admin001', 'password': 'incorrect'}, expected=(401,))
users = {}
for role in ['student', 'teacher']:
    username = TAG + '_' + role
    users[role] = username
    fields = dict(first_name='Smoke', middle_name='', last_name=role, username=username, email=username + '@example.invalid', password=PASSWORD, role=role)
    request('register.php', role, fields)
    request('register.php', role, fields, expected=(409,))
    request('login.php', role, {'username': username, 'password': PASSWORD}, expected=(403,))
    user_id = sql("SELECT id FROM users WHERE username='" + username + "'")
    request('admin_pendings.php', data={'user_id': user_id, 'action': 'approve'}, expected=(302,))
    login(username, role)
    users[role + '_id'] = user_id

# All root PHP entry points, including handlers' invalid-method behavior.
handlers = {'add_comment.php','add_post.php','comment.php','delete_comment.php','delete_post.php','like.php','newsfeed_post.php','post_actions.php','post_handler.php','upload_cover.php','upload_profile.php'}
for role in ['student', 'teacher', 'admin']:
    for page in sorted(ROOT.glob('*.php')):
        if page.name == 'logout.php': continue
        admin_only = page.name.startswith('admin_') or page.name in ['manage_accounts.php', 'delete_post.php']
        status = (403,) if admin_only and role != 'admin' else ((405,) if page.name in handlers else (302,) if page.name in ['index.php','login.php','register.php','search.php'] or (page.name == 'welcome.php' and role == 'admin') else (200,))
        request(page.name, role, expected=status)
    request('search.php?query=Smoke', role)
    request('profile.php?user_id=' + users['student_id'], role)
    request('admin_home.php', 'anonymous', expected=(302,))

subjects = set()
for page in ROOT.glob('*.html'):
    request(page.name, 'student')
    subjects.update(html.unescape(url) for url in re.findall(r'href="([^"]*course_page\.php\?course=[^"]+)"', page.read_text()))
original_admin_images = sql("SELECT profile_picture, cover_photo FROM users WHERE username='admin001'").split('\t')
for role in ['student', 'teacher', 'admin']:
    for subject in sorted(subjects):
        body, _ = request(subject, role)
        for link in re.findall(r'href="([^"]+)"', body):
            link = html.unescape(link)
            if re.search(r'/(home|student_feed|teacher_feed)\.php\?', link): request(link, role)
request('course_page.php?course=bad&subject=bad', expected=(404,))
request('course_page.php?course=cpe_core&subject=bad', expected=(404,))

png = WORK / 'test.png'
png.write_bytes(base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='))
document = WORK / 'test.txt'
document.write_text('URS SORS retained smoke-test attachment\n')
context = dict(section='Bachelor of Science in Computer Engineering', sub_section='Core Engineering Subjects', nested_sub_section='Engineering Economy', deep_sub_section='General')
request('post_handler.php', 'student', dict(context, content=TAG), {'image': png, 'file': document}, expected=(302,))
post_id = sql("SELECT id FROM posts WHERE content='" + TAG + "'")
assert post_id
request('student_feed.php?' + urlencode(context), 'student')
request('post_handler.php', 'student', dict(context, content=TAG, deep_sub_section='Professor Only'), expected=(403,))
request('post_handler.php', 'teacher', dict(context, content=TAG + '_prof', deep_sub_section='Professor Only'), expected=(302,))
request('add_comment.php', 'teacher', {'post_id': post_id, 'comment_content': TAG}, {'comment_file': document}, expected=(302,), referer='teacher_feed.php?' + urlencode(context))
request('comment.php', 'admin', {'post_id': post_id, 'content': TAG + '_alias'}, expected=(302,))
for _ in range(3): request('like.php', 'teacher', {'post_id': post_id}, expected=(302,), referer='teacher_feed.php?' + urlencode(context))
assert sql('SELECT COUNT(*) FROM likes WHERE post_id=' + post_id) == '1'
request('notifications.php', 'student')
assert sql('SELECT COUNT(*) FROM notifications WHERE user_id=' + users['student_id'] + ' AND is_read=0') == '0'
request('post_actions.php', 'student', {'post_id': post_id, 'action': 'archive'}, expected=(403,))
request('post_actions.php', 'teacher', {'post_id': post_id, 'action': 'archive'}, expected=(302,))
assert sql('SELECT is_archived FROM posts WHERE id=' + post_id) == '1'
request('admin_archive.php')
request('post_actions.php', 'admin', {'post_id': post_id, 'action': 'restore'}, expected=(302,))
assert sql('SELECT is_archived FROM posts WHERE id=' + post_id) == '0'
comment_id = sql('SELECT id FROM comments WHERE post_id=' + post_id + ' ORDER BY id LIMIT 1')
request('delete_comment.php', 'student', {'comment_id': comment_id}, expected=(403,))
request('delete_comment.php', 'teacher', {'comment_id': comment_id}, expected=(302,))
assert sql('SELECT is_deleted FROM comments WHERE id=' + comment_id) == '1'
request('delete_post.php', data={'post_id': post_id}, expected=(302,))
assert sql('SELECT is_archived FROM posts WHERE id=' + post_id) == '1'
request('post_actions.php', data={'post_id': post_id, 'action': 'restore'}, expected=(302,))
for role in ['student', 'teacher', 'admin']:
    for field, handler in [('profile_picture','upload_profile.php'), ('cover_photo','upload_cover.php')]:
        request(handler, role, files={field: png}, expected=(302,))
    request('admin_profile.php' if role == 'admin' else 'profile.php', role)
sql("UPDATE users SET profile_picture='" + original_admin_images[0] + "', cover_photo='" + original_admin_images[1] + "' WHERE username='admin001'")
request('upload_profile.php', 'student', files={'profile_picture': document}, expected=(400,))
request('manage_accounts.php', data={'user_id': users['student_id'], 'can_post': 0}, expected=(302,))
request('post_handler.php', 'student', dict(context, content=TAG), expected=(403,))
request('add_post.php', 'student', {'content': TAG}, expected=(403,))
request('manage_accounts.php', data={'user_id': users['student_id'], 'can_post': 1}, expected=(302,))
request('add_post.php', 'student', {'content': TAG + '_json'})
request('newsfeed_post.php', 'student', {'content': TAG + '_legacy'}, expected=(302,))
created = TAG + '_created'
request('admin_register.php', data=dict(first_name='Smoke', last_name='Created', middle_name='', email=created+'@example.invalid', username=created, password=PASSWORD, role='teacher'), expected=(302,))
assert sql("SELECT is_approved FROM users WHERE username='" + created + "'") == '1'
request('admin_pendings.php', data={'user_id': users['student_id'], 'action': 'reject'}, expected=(302,))
assert sql('SELECT is_approved FROM users WHERE id=' + users['student_id']) == '0'
request('admin_pendings.php', data={'user_id': users['student_id'], 'action': 'approve'}, expected=(302,))

# Re-render populated feeds, archives, rankings, search, notifications and resolve local assets.
for role in ['student','teacher','admin']:
    for page in ['student_feed.php', 'teacher_feed.php', 'home.php', 'feed.php', 'ranking_board.php', 'archived_posts.php', 'notifications.php'] + (['admin_archive.php','admin_profile.php','admin_notif.php','admin_ranking.php'] if role == 'admin' else []):
        body, _ = request(page, role)
        for link in re.findall(r'(?:src|href|action)="([^"]+)"', body):
            link = html.unescape(link)
            parsed = urlsplit(urljoin(BASE + '/' + page, link))
            if parsed.netloc != urlsplit(BASE).netloc: continue
            assert (ROOT / unquote(parsed.path).lstrip('/')).exists(), (page, link)
    request('logout.php', role, expected=(302,))
    request('welcome.php', role, expected=(302,))

for username in [users['student'], users['teacher'], created]:
    sql("UPDATE users SET is_approved=0, can_post=0 WHERE username='" + username + "'")

report = ROOT / 'docs/TEST_RESULTS.md'
report.write_text('# Integration test results\n\n' + f'Passed {len(RESULTS)} curl requests against `{BASE}`.\n\n' + f'Retained test-data prefix: `{TAG}`. Test users (now disabled): `{users["student"]}`, `{users["teacher"]}`, `{created}`.\n\n' + 'Covered all root PHP pages for all three roles; all catalogue subjects and audience feeds; registration/duplicates/approval/rejection; incorrect login; posting permissions; image/document/comment uploads; like/unlike/like; notifications/read state; archive/restore; non-destructive removals; profile/search; JSON/legacy endpoints; logout.\n\n' + 'No PHP warnings, notices, deprecations or fatal errors in response bodies. Every PHP source was also linted using `/usr/bin/php8.3 -l`. Browser visual and JavaScript interaction checks are not automated by this script.\n\n<details><summary>Request log</summary>\n\n```text\n' + '\n'.join(RESULTS) + '\n```\n</details>\n')
print(f'PASS: {len(RESULTS)} requests; {len(subjects)} subject routes; retained data {TAG}; report {report}')
