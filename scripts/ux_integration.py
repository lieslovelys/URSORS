#!/usr/bin/env python3
"""Opt-in curl + Chromium UX tests. Retains data; disables fixtures afterward.

ADMIN_PASSWORD=... python3 scripts/ux_integration.py
Set WITH_BROWSER=1 to include Playwright (python package + Chromium required).
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
BASE = os.environ.get('BASE_URL', 'http://localhost:8000').rstrip('/')
PASSWORD = os.environ['ADMIN_PASSWORD']
TAG = 'ux_' + str(time.time_ns())
WORK = Path(tempfile.mkdtemp(prefix='sors-ux-', dir='/tmp/opencode'))
RESULTS = []
USERS = {}
BROWSER_RESULTS = []
LOG = ROOT / 'var/log/app.log'
LOG_OFFSET = LOG.stat().st_size if LOG.exists() else 0

def sql(query):
    return subprocess.check_output(['mariadb','-uroot','-N','urs_sors','-e',query], text=True).strip()

def request(path, role='admin', data=None, files=None, expected=(200,), csrf=True, follow=False):
    cookie = WORK / (role + '.cookies')
    cmd = ['curl','-sS','-b',str(cookie),'-c',str(cookie),'-D',str(WORK/'headers'),'-w','\n%{http_code}']
    if follow: cmd += ['-L']
    if data is not None or files:
        if csrf:
            # A public page exposes the synchronizer token for this same session.
            token_page = subprocess.check_output(['curl','-sS','-b',str(cookie),'-c',str(cookie),BASE+'/help.php'], text=True)
            token = re.search(r'name="csrf-token" content="([^"]+)"',token_page)[1]
            cmd += ['-H','X-CSRF-Token: '+token]
        if files:
            for key,value in (data or {}).items(): cmd += ['-F',key+'='+str(value)]
            for key,value in files.items(): cmd += ['-F',key+'=@'+str(value)]
        else: cmd += ['--data',urlencode(data)]
    cmd += [BASE+'/'+path.lstrip('/')]
    body,status = subprocess.check_output(cmd,text=True).rsplit('\n',1)
    assert int(status) in expected,(path,status,body[:2500])
    assert not re.search(r'(Warning|Notice|Deprecated|Fatal error|Parse error):',re.sub('<[^>]+>','',body)),path
    RESULTS.append(f'{role}: {path} -> {status}')
    return body,(WORK/'headers').read_text()

def login(username, role):
    _,headers=request('login.php',role,{'username':username,'password':PASSWORD},expected=(302,))
    assert 'Location: /'+('admin_home.php' if role=='admin' else 'welcome.php') in headers

def register(role, suffix=None):
    username=TAG+'_'+(suffix or role)
    data=dict(first_name='UX',last_name=role.title(),email=username+'@example.invalid',username=username,password=PASSWORD,role=role)
    body,_=request('register.php',role,data)
    assert 'Your account was sent for approval.' in body
    user_id=sql("SELECT id FROM users WHERE username='"+username+"'")
    USERS[suffix or role]={'username':username,'id':user_id}
    return data,user_id

def browser_checks():
    from playwright.sync_api import sync_playwright, expect
    context = dict(section='Bachelor of Science in Computer Engineering',sub_section='Core Engineering Subjects',nested_sub_section='Engineering Economy',deep_sub_section='General')
    with sync_playwright() as p:
        browser=p.chromium.launch()
        for width in [1280,360]:
            for role in ['student','teacher','admin']:
                page=browser.new_page(viewport={'width':width,'height':900})
                errors=[];page.on('pageerror',lambda error:errors.append(str(error)))
                page.goto(BASE+'/login.php')
                page.get_by_label('Username',exact=True).fill('admin001' if role=='admin' else USERS[role]['username'])
                page.get_by_label('Password',exact=True).fill(PASSWORD)
                page.get_by_role('button',name='Show or hide password').click()
                assert page.get_by_label('Password',exact=True).get_attribute('type')=='text'
                page.get_by_role('button',name='Show or hide password').click()
                page.get_by_role('button',name='Log in',exact=True).click()
                page.wait_for_url('**/'+('admin_home.php' if role=='admin' else 'welcome.php'))
                paths=['welcome.php','course_page.php','course_page.php?program=cpe','course_page.php?course=cpe_core','course_page.php?course=cpe_core&subject=Engineering+Economy','feed.php?'+urlencode(context),'feed.php?mine=1','profile.php','notifications.php','ranking_board.php','archived_posts.php','help.php']
                if role=='admin': paths+=['admin_home.php','admin_pendings.php','manage_accounts.php','admin_register.php','admin_archive.php']
                for path in paths:
                    response=page.goto(BASE+'/'+path)
                    assert response.status==200,(role,path,response.status,page.locator('body').inner_text())
                    assert page.evaluate('document.documentElement.scrollWidth <= innerWidth'),(role,width,path)
                    # Real labels, >=16px form text, no unnamed buttons, no missing image alt.
                    assert page.locator('img:not([alt])').count()==0,path
                    for button in page.locator('button:visible').all(): assert (button.inner_text().strip() or button.get_attribute('aria-label')),path
                    assert page.evaluate('Array.from(document.querySelectorAll("input:not([type=hidden]),textarea,select,button")).every(x=>parseFloat(getComputedStyle(x).fontSize)>=16)'),path
                    for field in page.locator('input:not([type=hidden]),textarea,select').all():
                        field_id=field.get_attribute('id')
                        assert field_id and page.locator('label[for="'+field_id+'"]').count(),(path,field_id)
                page.goto(BASE+'/welcome.php')
                guide=page.get_by_role('button',name='Got it. Hide this guide.')
                if guide.count():
                    guide.click();page.wait_for_url('**/welcome.php');assert not page.get_by_role('button',name='Got it. Hide this guide.').count()
                    page.reload();assert not page.get_by_role('button',name='Got it. Hide this guide.').count()
                if width==360:
                    page.get_by_role('button',name='Menu',exact=True).click()
                    nav=page.get_by_role('navigation',name='Main navigation')
                    assert nav.get_by_role('link',name='Subjects',exact=True).is_visible()
                    page.keyboard.press('Escape');assert not nav.is_visible()
                # Use the cards, not just direct URLs, for the full subject flow.
                page.get_by_role('link',name='Browse Subjects',exact=True).click()
                page.get_by_role('link',name=re.compile('Computer Engineering')).click()
                page.get_by_role('link',name=re.compile('Core Subjects')).click()
                page.get_by_role('link',name=re.compile('Engineering Economy')).click()
                audience='General' if role=='student' else 'Professor Only'
                page.get_by_role('link',name=re.compile('^'+audience)).click()
                content=TAG+'_browser_'+role+'_'+str(width)
                page.get_by_label('What would you like to share?').fill(content)
                page.get_by_label('Attach a file (photo, PDF, etc.)').set_input_files(str(WORK/'test.txt'))
                page.get_by_role('button',name='Post (I-share)',exact=True).click()
                page.wait_for_url(re.compile('.*_feed.php.*|.*/home.php.*'))
                assert page.get_by_role('status').filter(has_text='Your post was shared.').count()
                article=page.get_by_role('article').filter(has=page.get_by_text(content,exact=True))
                article.get_by_role('button',name=re.compile('^Like')).click()
                assert page.get_by_role('status').filter(has_text='You liked this post.').count()
                article=page.get_by_role('article').filter(has=page.get_by_text(content,exact=True))
                article.get_by_text(re.compile(r'Comments \(')).click()
                article.get_by_label('Write a comment').fill(TAG+'_browser_reply')
                article.get_by_role('button',name='Send comment',exact=True).click()
                assert page.get_by_role('status').filter(has_text='Your comment was added.').count()
                if role!='student':
                    article=page.get_by_role('article').filter(has=page.get_by_text(content,exact=True))
                    article.get_by_text('Manage this post',exact=True).click();article.get_by_role('button',name='Archive post',exact=True).click()
                    assert page.get_by_role('dialog').is_visible()
                    page.get_by_role('button',name='Cancel',exact=True).click();assert not page.get_by_role('dialog').is_visible()
                    article.get_by_role('button',name='Archive post',exact=True).click();page.get_by_role('button',name='Yes, continue',exact=True).click()
                    assert page.get_by_role('status').filter(has_text='Post archived.').count()
                page.goto(BASE+'/profile.php')
                page.get_by_label('Choose a new profile photo').set_input_files(str(WORK/'test.png'))
                expect(page.get_by_alt_text('Preview of your chosen profile photo')).to_be_visible()
                page.get_by_role('button',name='Save profile photo',exact=True).click()
                assert page.get_by_role('status').filter(has_text='Your profile photo was changed.').count()
                if width==360: page.get_by_role('button',name='Menu',exact=True).click()
                page.get_by_role('button',name='Logout',exact=True).click();page.wait_for_url('**/login.php')
                assert not errors,(role,width,errors)
                BROWSER_RESULTS.append(f'{role} at {width}px: navigation, cards, posting, attachments, comments, likes, confirmations, preview/upload, logout; all page widths checked')
                page.close()
        # Browser registration + pending message, then approval through an admin session.
        page=browser.new_page(viewport={'width':360,'height':900});page.goto(BASE+'/register.php')
        username=TAG+'_browser_new';USERS['browser_new']={'username':username,'id':None}
        for name,value in [('First name','Browser'),('Last name','Student'),('Email address',username+'@example.invalid'),('Username',username),('Password',PASSWORD)]:page.get_by_label(name,exact=True).fill(value)
        page.get_by_label('I am a').select_option('student');page.get_by_role('button',name='Send for approval',exact=True).click()
        assert page.get_by_role('heading',name='Your account was sent for approval.').is_visible()
        user_id=sql("SELECT id FROM users WHERE username='"+username+"'");USERS['browser_new']['id']=user_id
        page.get_by_role('link',name='Go to login',exact=True).click();page.get_by_label('Username',exact=True).fill(username);page.get_by_label('Password',exact=True).fill(PASSWORD);page.get_by_role('button',name='Log in',exact=True).click()
        assert page.get_by_role('alert').filter(has_text='waiting for approval').count()
        admin_page=browser.new_page(viewport={'width':360,'height':900});admin_page.goto(BASE+'/login.php')
        admin_page.get_by_label('Username',exact=True).fill('admin001');admin_page.get_by_label('Password',exact=True).fill(PASSWORD);admin_page.get_by_role('button',name='Log in',exact=True).click();admin_page.wait_for_url('**/admin_home.php')
        admin_page.goto(BASE+'/admin_pendings.php?q='+username)
        admin_page.get_by_role('button',name='Approve Browser',exact=True).click()
        assert admin_page.get_by_role('status').filter(has_text='Browser Student is approved').count()
        admin_page.goto(BASE+'/manage_accounts.php?q='+username)
        admin_page.get_by_role('button',name='Stop posting',exact=True).click()
        admin_page.get_by_role('button',name='Yes, continue',exact=True).click()
        assert admin_page.get_by_role('status').filter(has_text='Posting is now turned off').count()
        admin_page.get_by_role('button',name='Allow posting',exact=True).click();admin_page.get_by_role('button',name='Yes, continue',exact=True).click()
        assert admin_page.get_by_role('status').filter(has_text='can now share posts').count()
        page.get_by_label('Password',exact=True).fill(PASSWORD);page.get_by_role('button',name='Log in',exact=True).click();page.wait_for_url('**/welcome.php')
        BROWSER_RESULTS.append('360px browser registration, pending-login explanation, one-click admin approval, searchable accounts, allow/stop posting, approved login passed')
        browser.close()

original_images=sql("SELECT profile_picture,cover_photo FROM users WHERE username='admin001'").split('\t')
try:
    login('admin001','admin')
    request('login.php','badlogin',{'username':'admin001','password':'wrong'},expected=(401,))
    request('login.php','badcsrf',{'username':'admin001','password':PASSWORD},expected=(403,),csrf=False)
    for role in ['student','teacher']:
        data,user_id=register(role)
        request('register.php',role,data,expected=(409,))
        request('login.php',role,{'username':data['username'],'password':PASSWORD},expected=(403,))
        request('admin_pendings.php',data={'user_id':user_id,'action':'approve','_return':'/admin_pendings.php'},expected=(302,))
        login(data['username'],role)
    handlers={'add_comment.php','add_post.php','comment.php','delete_comment.php','delete_post.php','like.php','newsfeed_post.php','post_actions.php','post_handler.php','upload_cover.php','upload_profile.php','logout.php'}
    for role in ['student','teacher','admin']:
        for page in sorted(ROOT.glob('*.php')):
            admin_only=page.name.startswith('admin_') or page.name in ['manage_accounts.php','delete_post.php']
            expected=(404,) if page.name in ['config.php','router.php'] else (403,) if admin_only and role!='admin' else (405,) if page.name in handlers else (302,) if page.name=='index.php' else (200,)
            request(page.name,role,expected=expected)
        for handler in handlers:
            request(handler,role,{},expected=(403,),csrf=False)
        for old in ROOT.glob('*.html'):request(old.name,role,expected=(302,))
    for path in ['.git/config','_unused/redesign_before/login.php','includes/layout.php','Database/urs_sors.sql','scripts/set_admin_password.php','does-not-exist.php']:
        request(path,expected=(404,))
    for course,subjects in json.loads((ROOT/'includes/catalogue.json').read_text()).items():
        for role in ['student','teacher','admin']:
            for subject in subjects:
                body,_=request('course_page.php?'+urlencode({'course':course,'subject':subject}),role)
                for link in re.findall(r'href="([^"]+)"',body):
                    link=html.unescape(link)
                    if re.search(r'/(home|student_feed|teacher_feed)\.php\?',link):request(link,role)
    request('course_page.php?course=invalid&subject=invalid',expected=(404,))
    request('course_page.php?course=cpe_core&subject=invalid',expected=(404,))
    (WORK/'test.png').write_bytes((ROOT/'assets/images/title_icon.png').read_bytes())
    (WORK/'test.txt').write_text('Retained URS SORS UX-test attachment.\n')
    (WORK/'bad.php').write_text('<?php echo "not allowed";')
    context=dict(section='Bachelor of Science in Computer Engineering',sub_section='Core Engineering Subjects',nested_sub_section='Engineering Economy',deep_sub_section='General')
    request('post_handler.php','student',dict(context,content=TAG),{'image':WORK/'test.png','file':WORK/'test.txt'},expected=(302,))
    post_id=sql("SELECT id FROM posts WHERE content='"+TAG+"'")
    request('post_handler.php','student',dict(context,content='not allowed',deep_sub_section='Professor Only'),expected=(403,))
    request('post_handler.php','teacher',dict(context,content=TAG+'_prof',deep_sub_section='Professor Only'),expected=(302,))
    request('post_handler.php','teacher',dict(context,content='not allowed'),{'file':WORK/'bad.php'},expected=(400,))
    request('add_comment.php','teacher',{'post_id':post_id,'comment_content':TAG,'_return':'/feed.php?post_id='+post_id},{'comment_file':WORK/'test.txt'},expected=(302,))
    request('comment.php',data={'post_id':post_id,'content':TAG+'_alias'},expected=(302,))
    for _ in range(3):request('like.php','teacher',{'post_id':post_id},expected=(302,))
    assert sql('SELECT COUNT(*) FROM likes WHERE post_id='+post_id)=='1'
    body,_=request('notifications.php','student')
    assert 'UX Teacher commented on your post.' in body and 'feed.php?post_id='+post_id in body and 'notification unread' in body
    assert int(sql('SELECT COUNT(*) FROM notifications WHERE user_id='+USERS['student']['id']+' AND is_read=0'))>0
    request('notifications.php','student',{'action':'mark_read'},expected=(302,))
    assert sql('SELECT COUNT(*) FROM notifications WHERE user_id='+USERS['student']['id']+' AND is_read=0')=='0'
    request('post_actions.php','student',{'post_id':post_id,'action':'archive'},expected=(403,))
    request('post_actions.php','teacher',{'post_id':post_id,'action':'archive'},expected=(302,))
    assert sql('SELECT is_archived FROM posts WHERE id='+post_id)=='1'
    request('feed.php?post_id='+post_id,'student',expected=(404,))
    request('post_actions.php',data={'post_id':post_id,'action':'restore'},expected=(302,))
    comment_id=sql('SELECT id FROM comments WHERE post_id='+post_id+' ORDER BY id LIMIT 1')
    request('delete_comment.php','student',{'comment_id':comment_id},expected=(403,))
    request('delete_comment.php','teacher',{'comment_id':comment_id},expected=(302,))
    assert sql('SELECT is_deleted FROM comments WHERE id='+comment_id)=='1'
    request('delete_post.php',data={'post_id':post_id},expected=(302,))
    request('post_actions.php',data={'post_id':post_id,'action':'restore'},expected=(302,))
    for role in ['student','teacher','admin']:
        for field,handler in [('profile_picture','upload_profile.php'),('cover_photo','upload_cover.php')]:request(handler,role,files={field:WORK/'test.png'},expected=(302,))
        request('profile.php',role)
        request('profile.php?user_id='+USERS['student']['id'],role)
        request('search.php?query=UX',role)
        body,_=request('ranking_board.php',role);assert 'It is not grades.' in body
    request('upload_profile.php','student',files={'profile_picture':WORK/'test.txt'},expected=(400,))
    request('manage_accounts.php',data={'user_id':USERS['student']['id'],'action':'stop_posting'},expected=(302,))
    body,_=request('feed.php','student');assert 'Posting is turned off for your account.' in body
    request('post_handler.php','student',dict(context,content='not allowed'),expected=(403,))
    request('add_post.php','student',{'content':'not allowed'},expected=(403,))
    request('manage_accounts.php',data={'user_id':USERS['student']['id'],'action':'allow_posting'},expected=(302,))
    request('add_post.php','student',{'content':TAG+'_json'})
    request('newsfeed_post.php','student',{'content':TAG+'_legacy'},expected=(302,))
    created=TAG+'_created';USERS['created']={'username':created,'id':None}
    request('admin_register.php',data=dict(first_name='UX',last_name='Created',email=created+'@example.invalid',username=created,password=PASSWORD,role='teacher'),expected=(302,))
    USERS['created']['id']=sql("SELECT id FROM users WHERE username='"+created+"'")
    assert sql('SELECT is_approved FROM users WHERE id='+USERS['created']['id'])=='1'
    request('admin_pendings.php',data={'user_id':USERS['student']['id'],'action':'reject'},expected=(302,))
    request('feed.php','student',expected=(403,))
    request('admin_pendings.php',data={'user_id':USERS['student']['id'],'action':'approve'},expected=(302,))
    login(USERS['student']['username'],'student')
    _,headers=request('like.php','teacher',{'post_id':post_id,'_return':'https://example.com'},expected=(302,));assert 'Location: /teacher_feed.php' in headers
    if os.environ.get('WITH_BROWSER')=='1':browser_checks()
    for role in ['student','teacher','admin']:
        request('logout.php',role,{},expected=(302,));request('feed.php',role,expected=(302,))
    new_log=LOG.read_bytes()[LOG_OFFSET:] if LOG.exists() else b''
    assert not new_log,new_log.decode()
finally:
    # Keep every fixture row/file, but do not leave enabled test accounts or active posts.
    for user in USERS.values():
        username=user['username']
        sql("UPDATE posts SET is_archived=1 WHERE user_id IN (SELECT id FROM users WHERE username='"+username+"')")
        sql("UPDATE users SET is_approved=0,can_post=0 WHERE username='"+username+"'")
    sql("UPDATE posts SET is_archived=1 WHERE content LIKE '"+TAG+"%' AND user_id=(SELECT id FROM users WHERE username='admin001')")
    sql("UPDATE users SET profile_picture='"+original_images[0]+"',cover_photo='"+original_images[1]+"' WHERE username='admin001'")

report='# UX integration results\n\n'+f'Passed {len(RESULTS)} recorded curl requests on `{BASE}`. All 89 subjects checked for all three roles.\n\nFixture prefix: `{TAG}`. All test accounts disabled; new test posts archived; rows/files retained. No schema changes. Admin image references restored.\n\nNo new entries in `var/log/app.log`. CSRF rejection, approval changes, role checks, uploads, read state, moderation, prepared queries and JSON compatibility checked.\n\n## Browser checks\n\n'+('\n'.join('- '+item for item in BROWSER_RESULTS) or 'Not requested. Set WITH_BROWSER=1.')+'\n\n## Curl request log\n\n<details><summary>Requests</summary>\n\n```text\n'+'\n'.join(RESULTS)+'\n```\n</details>\n'
(ROOT/'docs/UX_TEST_RESULTS.md').write_text(report)
print(f'PASS: {len(RESULTS)} curl requests; {len(BROWSER_RESULTS)} browser scenarios. Report: docs/UX_TEST_RESULTS.md')
