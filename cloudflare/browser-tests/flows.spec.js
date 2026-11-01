import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { hashPassword } from '../functions/lib/security.js';

test('administrator portal, complete sidebar and independent community Home', async ({ page, request }) => {
  const username='portal_'+Date.now(), password='Portal test password 2026';
  const sql=command=>execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',command]);
  sql(`INSERT INTO users(username,email,name,password,role,approved) VALUES ('${username}','${username}@example.invalid','Portal Test Admin','${await hashPassword(password)}','admin',1)`);
  try {
    await page.goto('/#login');await page.locator('#login-user').fill(username);await page.locator('#login-password').fill(password);await page.locator('#login button[type=submit]').click();
    await expect(page).toHaveURL(/#admin$/);await expect(page.getByRole('heading',{name:'Admin Portal',exact:true})).toBeVisible();
    await page.locator('[data-page=admin]').getByRole('link',{name:'Manage accounts →'}).click();await expect(page.locator('[data-page=accounts]')).toBeVisible();
    await expect(page.locator('#site-nav .tab-label')).toHaveCount(12);
    for(const [route,heading] of [['pending','Account management'],['accounts','Account management'],['notifications','Notifications'],['profile','My profile'],['subjects','Subjects'],['ranking','Community ranking'],['admin','Admin Portal'],['create-account','Create an account'],['archives','Archived posts'],['help','How can we help?']]){
      await page.goto('/#'+route);await expect(page.getByRole('heading',{name:heading,exact:true})).toBeVisible();
    }
    await page.locator('#site-nav').getByRole('link',{name:'Home',exact:true}).click();await expect(page).toHaveURL(/#home$/);
    await expect(page.getByRole('heading',{name:'Community posts',exact:true})).toBeVisible();
    await page.reload();await expect(page.locator('[data-page=home]')).toBeVisible();
    await page.locator('#search-query').fill(username);await page.getByRole('button',{name:'Search accounts',exact:true}).click();await expect(page.locator('#search-results')).toContainText('Portal Test Admin');
    await page.goto('/#profile');await page.locator('#profile-input').fill('Updated Portal Admin');await page.getByRole('button',{name:'Save profile',exact:true}).click();await expect(page.locator('#account-name')).toHaveText('Updated Portal Admin');
    await page.goto('/#create-account');await page.locator('#create-name').fill('Created Student');await page.locator('#create-email').fill(username+'_student@example.invalid');await page.locator('#create-username').fill(username+'_student');await page.locator('#create-password').fill('Created student password');await page.getByRole('button',{name:'Create approved account'}).click();await expect(page).toHaveURL(/#accounts$/);await expect(page.locator('#users')).toContainText('Created Student');
    sql(`UPDATE users SET approved=0,can_post=0 WHERE username='${username}_student'`);
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
    await page.goto('/#profile');await page.locator('#logout').click();await page.locator('#confirm-yes').click();await expect(page.locator('#login')).toBeVisible();
    expect(errors).toEqual([]);
  } finally {
    execFileSync('npx',['wrangler','d1','execute','ursors','--local','--command',`UPDATE users SET approved=0,can_post=0 WHERE username='${username}'`]);
  }
});
