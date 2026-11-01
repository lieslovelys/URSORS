import { test, expect, request as requestFactory } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { hashPassword } from '../functions/lib/security.js';

test('public landing page is the default with working login and register links', async ({ page }) => {
  await page.goto('/');await expect(page.locator('#landing-screen')).toBeVisible();await expect(page.locator('#auth')).toBeHidden();
  await expect(page.getByRole('heading',{name:'University of Rizal System',exact:true})).toBeVisible();
  await page.locator('#landing-screen').getByRole('link',{name:'Log in',exact:true}).click();await expect(page.locator('#login')).toBeVisible();
  await page.locator('.brand').click();await expect(page.locator('#landing-screen')).toBeVisible();
  await page.locator('#landing-screen').getByRole('link',{name:'Register',exact:true}).click();await expect(page.locator('#register')).toBeVisible();
  await page.goto('/');
  for(const width of [1440,390]){
    await page.setViewportSize({width,height:900});await expect(page.locator('#landing-screen')).toBeVisible();
    expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
    await page.screenshot({path:`test-results/landing-${width}.png`,fullPage:true});
  }
});

test('administrator portal, complete sidebar and independent community Home', async ({ page, request }) => {
  const username='portal_'+Date.now(), password='Portal test password 2026';
  const sql=command=>execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',command]);
  sql(`INSERT INTO users(username,email,name,password,role,approved) VALUES ('${username}','${username}@example.invalid','Portal Test Admin','${await hashPassword(password)}','admin',1)`);
  try {
    await page.goto('/#login');await page.locator('#login-user').fill(username);await page.locator('#login-password').fill(password);await page.locator('#login button[type=submit]').click();
    await expect(page).toHaveURL(/#admin$/);await expect(page.getByRole('heading',{name:'Admin Portal',exact:true})).toBeVisible();
    await page.locator('[data-page=admin]').getByRole('link',{name:'Manage accounts →'}).click();await expect(page.locator('[data-page=accounts]')).toBeVisible();
    await expect(page.locator('#site-nav .tab-label')).toHaveCount(12);
    for(const [route,heading] of [['pending','Pending Accounts'],['accounts','All Accounts'],['notifications','Notifications'],['profile','My profile'],['subjects','Subjects'],['ranking','Community ranking'],['admin','Admin Portal'],['create-account','Create an account'],['archives','Archived posts'],['help','How can we help?']]){
      await page.goto('/#'+route);await expect(page.getByRole('heading',{name:heading,exact:true})).toBeVisible();
    }
    await page.locator('#site-nav').getByRole('link',{name:'Home',exact:true}).click();await expect(page).toHaveURL(/#home$/);
    await expect(page.getByRole('heading',{name:'Community posts',exact:true})).toBeVisible();
    await page.reload();await expect(page.locator('[data-page=home]')).toBeVisible();
    await page.locator('#search-query').fill(username);await page.getByRole('button',{name:'Search accounts',exact:true}).click();await expect(page.locator('#search-results')).toContainText('Portal Test Admin');
    await page.goto('/#profile');await expect(page.locator('#edit-profile')).toBeHidden();await page.getByRole('button',{name:'Edit profile',exact:true}).click();await page.locator('#profile-input').fill('Updated Portal Admin');await page.locator('#edit-profile').getByRole('button',{name:'Save',exact:true}).click();await expect(page.locator('#account-name')).toHaveText('Updated Portal Admin');await expect(page.locator('#edit-profile')).toBeHidden();await expect(page.locator('#profile-name')).toHaveText('Updated Portal Admin');
    await page.goto('/#create-account');await page.locator('#create-name').fill('Created Student');await page.locator('#create-email').fill(username+'_student@example.invalid');await page.locator('#create-username').fill(username+'_student');await page.locator('#create-password').fill('Created student password');await page.getByRole('button',{name:'Create approved account'}).click();await expect(page).toHaveURL(/#accounts$/);await expect(page.locator('#users')).toContainText('Created Student');
    sql(`UPDATE users SET approved=0,can_post=0 WHERE username='${username}_student'`);
    sql(`INSERT INTO notifications(user_id,message,target_user_id) SELECT a.id,'Review Created Student',s.id FROM users a,users s WHERE a.username='${username}' AND s.username='${username}_student'`);
    await page.goto('/#notifications');
    await page.locator('.notification-main').filter({hasText:'Review Created Student'}).click();
    await expect(page).toHaveURL(/#pending\?account=\d+$/);
    await expect(page.locator('#users article')).toHaveCount(1);
    await expect(page.locator('#users .notification-target')).toContainText('Created Student');
    await page.goto('/#notifications');
    await expect(page.locator('.notification-card').filter({hasText:'Review Created Student'})).not.toHaveClass(/is-unread/);
  } finally {sql(`UPDATE users SET approved=0,can_post=0 WHERE username='${username}'`);}
});

test('separate auth screens, guided subjects, discussion, archives and mobile layout', async ({ page, request }) => {
  const errors=[]; page.on('pageerror', error=>errors.push(error.message));
  const username='browser_'+Date.now(); const password='Browser test password 2026';
  execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',`INSERT INTO users(username,email,name,password,role,approved) VALUES ('${username}','${username}@example.invalid','Browser Test Teacher','${await hashPassword(password)}','teacher',1)`]);
  try {
    await page.goto('/#login');
    await expect(page.locator('#login')).toBeVisible(); await expect(page.locator('#register')).toBeHidden();
    await page.locator('#login-password').fill(password);
    await page.locator('[data-password="login-password"]').click();
    await expect(page.locator('#login-password')).toHaveAttribute('type','text');
    await page.locator('#login-user').fill(username);
    await page.locator('#login button[type=submit]').click();
    await expect(page.locator('[data-page=home]')).toBeVisible();
    await page.getByRole('link',{name:'Browse Subjects',exact:true}).click();
    await page.locator('#subject-browser .card').first().click();
    await page.locator('#subject-browser .card').first().click();
    await page.locator('#subject-browser .subject-link').first().click();
    await expect(page.locator('#publish')).toBeVisible();
    const resourceText='Browser-tested resource '+username;
    await page.locator('#post-content').fill(resourceText);
    await page.locator('#post-link').fill('https://example.org/material');
    await page.getByRole('button',{name:'Publish post',exact:true}).click();
    const post=page.locator('#feed article').filter({hasText:resourceText}).first();
    await expect(post).toBeVisible();
    await post.getByRole('button',{name:'Comments',exact:true}).click();
    await post.locator('textarea').fill('Test discussion');
    await post.getByRole('button',{name:'Post comment',exact:true}).click();
    await expect(post.locator('.comment-content')).toHaveText('Test discussion');
    await post.getByRole('button',{name:'Remove comment',exact:true}).click();await page.locator('#confirm-yes').click();await expect(post.locator('.comment-content')).toHaveCount(0);
    await post.getByRole('button',{name:'Archive',exact:true}).click();
    await page.locator('#confirm-yes').click();
    await expect(post).toHaveCount(0);
    await page.goto('/#archives');
    const archived=page.locator('#archive-list article').filter({hasText:resourceText}).first();
    await archived.getByRole('button',{name:'Restore to feed'}).click();
    await expect(archived).toHaveCount(0);
    await page.goto('/#ranking');await expect(page.locator('#ranking-list')).toContainText('Browser Test Teacher');
    for(const width of [1440,390]) {
      await page.setViewportSize({width,height:900});await page.goto('/#home');
      await expect(page.locator('[data-page=home]')).toBeVisible();
      expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
      await page.screenshot({path:`test-results/home-${width}.png`,fullPage:true});
    }
    for(const width of [1440,390]) {
      await page.setViewportSize({width,height:900});await page.goto('/#profile');
      await expect(page.locator('#profile-name')).toHaveText('Browser Test Teacher');
      await expect(page.locator('#profile-username')).toHaveText('@'+username);
      await expect(page.locator('#profile-feed article')).toHaveCount(1);
      await expect(page.locator('#profile-feed')).toContainText(resourceText);
      await expect(page.locator('#profile-feed .resource-link')).toHaveAttribute('href','https://example.org/material');
      if(width===1440){
        const composer=await page.locator('#profile-publish').boundingBox();
        const account=await page.locator('.profile-secondary').boundingBox();
        const posts=await page.locator('.profile-posts').boundingBox();
        expect(Math.abs(composer.y-account.y)).toBeLessThan(2);
        expect(Math.abs(composer.y+composer.height-account.y-account.height)).toBeLessThan(2);
        expect(posts.y).toBeGreaterThan(account.y+account.height);
        expect(posts.width).toBeGreaterThan(composer.width);
      }
      await expect(page.locator('#edit-profile')).toBeHidden();
      await page.getByRole('button',{name:'Edit profile',exact:true}).click();await expect(page.locator('#profile-input')).toBeFocused();
      await page.locator('#profile-input').fill('Unsaved change');await page.getByRole('button',{name:'Cancel',exact:true}).click();await expect(page.locator('#profile-name')).toHaveText('Browser Test Teacher');await expect(page.locator('#edit-profile')).toBeHidden();
      expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
      await page.screenshot({path:`test-results/profile-${width}.png`,fullPage:true});
    }
    await page.locator('#profile-content').fill('Created from profile '+username);
    await page.locator('#profile-resource').fill('https://example.org/profile-resource');
    await page.locator('#profile-publish').getByRole('button',{name:'Publish post',exact:true}).click();
    const profilePost=page.locator('#profile-feed article').filter({hasText:'Created from profile '+username});
    await expect(profilePost).toBeVisible();await expect(page.locator('#profile-content')).toBeEmpty();
    await profilePost.locator('.post-tags a').click();
    await expect(page.locator('#feed')).toContainText('Created from profile '+username);
    execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',`INSERT INTO notifications(user_id,message,post_id) SELECT u.id,'Open exact profile discussion',p.id FROM users u JOIN posts p ON p.user_id=u.id WHERE u.username='${username}' AND p.content='Created from profile ${username}'`]);
    await page.goto('/#notifications');await page.locator('.notification-main').filter({hasText:'Open exact profile discussion'}).click();
    await expect(page).toHaveURL(/&post=\d+$/);await expect(page.locator('#feed article')).toHaveCount(1);
    await expect(page.locator('#feed')).toContainText('Created from profile '+username);await expect(page.locator('#feed .comment-form')).toBeVisible();
    await page.goto('/#profile');await page.getByRole('button',{name:'Change avatar',exact:true}).click();
    await page.locator('#avatar-options input[value=teal]').check();await page.getByRole('button',{name:'Save avatar',exact:true}).click();
    await expect(page.locator('#avatar-dialog')).not.toBeVisible();
    await expect(page.locator('.profile-portrait')).toHaveAttribute('src','/assets/images/avatars/teal.svg');
    await expect(page.locator('#account img')).toHaveAttribute('src','/assets/images/avatars/teal.svg');
    await expect(page.locator('#profile-feed .post-author img').first()).toHaveAttribute('src','/assets/images/avatars/teal.svg');
    await page.reload();await expect(page.locator('.profile-portrait')).toHaveAttribute('src','/assets/images/avatars/teal.svg');
    await page.getByRole('button',{name:'Change avatar',exact:true}).click();await page.locator('#avatar-options input[value=rose]').check();await page.locator('#cancel-avatar').click();
    await expect(page.locator('.profile-portrait')).toHaveAttribute('src','/assets/images/avatars/teal.svg');
    await page.goto('/#profile');await page.locator('#logout').click();await page.locator('#confirm-yes').click();await expect(page.locator('#login')).toBeVisible();
    expect(errors).toEqual([]);
  } finally {
    execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',`UPDATE users SET approved=0,can_post=0 WHERE username='${username}'`]);
  }
});

test('notification ownership, account creation, approval and comment moderation permissions', async () => {
  const prefix='access_'+Date.now(), password='Permission test password 2026';
  const sql=command=>execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',command]);
  const encoded=await hashPassword(password);
  const accounts=['owner','other','admin'];
  sql(accounts.map(role=>`INSERT INTO users(username,email,name,password,role,approved) VALUES ('${prefix}_${role}','${prefix}_${role}@example.invalid','Access ${role}','${encoded}','${role==='admin'?'admin':'student'}',1)`).join(';'));
  const clients={};
  try {
    for(const role of accounts){
      clients[role]=await requestFactory.newContext({baseURL:'http://localhost:8788',extraHTTPHeaders:{Origin:'http://localhost:8788'}});
      const response=await clients[role].post('/api/login',{data:{username:prefix+'_'+role,password}});expect(response.status()).toBe(200);
    }
    const owner=clients.owner,other=clients.other,admin=clients.admin;
    expect((await owner.post('/api/profile/avatar',{data:{avatar:'https://evil.example/avatar.svg'}})).status()).toBe(400);
    const otherId=(await (await other.get('/api/me')).json()).user.id;
    expect((await owner.post('/api/profile/avatar',{data:{avatar:'purple',id:otherId}})).status()).toBe(200);
    expect((await (await other.get('/api/me')).json()).user.avatar).toBe('default');
    expect((await (await owner.get('/api/me')).json()).user.avatar).toBe('purple');
    const catalogue=await (await owner.get('/catalogue.json')).json();const category=Object.keys(catalogue)[0];
    const resource={content:prefix,category,subject:catalogue[category][0],audience:'general',resource_url:'https://example.org/resource'};
    expect((await owner.post('/api/posts',{data:resource})).status()).toBe(201);
    const posts=await (await owner.get('/api/posts')).json();const id=posts.posts.find(p=>p.content===prefix).id;
    await other.post(`/api/posts/${id}/like`,{data:{liked:true}});await other.post(`/api/posts/${id}/like`,{data:{liked:true}});
    await other.post(`/api/posts/${id}/comments`,{data:{content:'Permission test comment'}});
    const comments=await (await owner.get(`/api/posts/${id}/comments`)).json();const commentId=comments.comments[0].id;
    expect((await owner.post(`/api/comments/${commentId}/remove`,{data:{}})).status()).toBe(403);
    expect((await admin.post(`/api/comments/${commentId}/remove`,{data:{}})).status()).toBe(200);
    expect((await (await owner.get(`/api/posts/${id}/comments`)).json()).comments).toHaveLength(0);
    const notifications=await (await owner.get('/api/notifications')).json();expect(notifications.unread).toBe(2);
    const first=notifications.notifications[0];
    await other.post('/api/notifications/read',{data:{id:first.id}});
    expect((await (await owner.get('/api/notifications')).json()).unread).toBe(2);
    await owner.post('/api/notifications/read',{data:{}});expect((await (await owner.get('/api/notifications')).json()).unread).toBe(0);
    const created={username:prefix+'_created',email:prefix+'_created@example.invalid',name:'Created permission test',password,role:'student'};
    expect((await other.post('/api/admin/create-account',{data:created})).status()).toBe(403);
    expect((await admin.post('/api/admin/create-account',{data:created})).status()).toBe(201);
    const users=await (await admin.get('/api/admin/users')).json();const target=users.users.find(u=>u.username===prefix+'_other');
    expect((await admin.post('/api/admin/users',{data:{id:target.id,approved:false,can_post:false}})).status()).toBe(200);
    expect((await other.get('/api/me')).status()).toBe(401);
    expect((await owner.get('/api/admin/stats')).status()).toBe(403);
  } finally {
    sql(`UPDATE users SET approved=0,can_post=0 WHERE username LIKE '${prefix}%'`);
    await Promise.all(Object.values(clients).map(client=>client.dispose()));
  }
});
