import { fail, text, resourceLink, validateAccount, randomToken, digest, hashPassword, verifyPassword, checkOrigin, DUMMY_PASSWORD_HASH } from '../lib/security.js';
import { AVATARS } from '../../public/avatars.js';

const publicUser = u => ({ id: u.id, username: u.username, name: u.name, role: u.role, approved: u.approved, can_post: u.can_post, avatar: u.avatar || 'default' });
const now = () => Math.floor(Date.now() / 1000);
const json = (data, status = 200, extra = {}) => new Response(JSON.stringify(data), { status, headers: {
  'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store',
  'X-Content-Type-Options': 'nosniff', ...extra
} });
const query = (db, sql, ...args) => db.prepare(sql).bind(...args);
const notify = (db, id, message, post = null) => query(db, 'INSERT INTO notifications(user_id,message,post_id) VALUES (?,?,?)', id, message, post).run();
async function rateLimit(db, request, action) {
  const ip = request.headers.get('CF-Connecting-IP') || 'local';
  const bucket = await digest(`${action}:${ip}:${Math.floor(now() / 900)}`);
  const row = await query(db, `INSERT INTO attempts(bucket,count,expires) VALUES (?,1,?)
    ON CONFLICT(bucket) DO UPDATE SET count=count+1 RETURNING count`, bucket, now() + 900).first();
  if (row.count > (action === 'register' ? 5 : 20)) fail('Too many attempts. Try again in 15 minutes.', 429);
  await query(db, 'DELETE FROM attempts WHERE expires < ?', now()).run();
}
async function session(db, request) {
  const token = /(?:^|;\s*)sors_session=([a-f0-9]{64})(?:;|$)/.exec(request.headers.get('Cookie') || '')?.[1];
  if (!token) return null;
  return query(db, `SELECT u.*, s.token_hash FROM sessions s JOIN users u ON u.id=s.user_id
    WHERE s.token_hash=? AND s.expires>? AND u.approved=1`, await digest(token), now()).first();
}
export async function onRequest({ request, env }) {
  try {
    const db = env.DB;
    if (!db) fail('Database is not configured.', 503);
    const path = new URL(request.url).pathname.replace(/^\/api\/?/, '');
    const method = request.method;
    if (!['GET', 'POST'].includes(method)) return json({ error: 'Method not allowed.' }, 405, { Allow: 'GET, POST' });
    let body = {};
    if (method === 'POST') {
      checkOrigin(request);
      const raw = await request.text();
      if (raw.length > 16384) fail('Request is too large.', 413);
      try { body = JSON.parse(raw); } catch { fail('Invalid JSON.'); }
      if (!body || typeof body !== 'object' || Array.isArray(body)) fail('Invalid request.');
    }
    if (path === 'register' && method === 'POST') {
      await rateLimit(db, request, 'register');
      const account = validateAccount(body);
      const password = await hashPassword(account.password);
      let registration;
      try {
        registration = await query(db, 'INSERT INTO users(username,email,name,password,role) VALUES (?,?,?,?,?)', account.username, account.email, account.name, password, account.role).run();
      } catch (error) {
        if (String(error.message).includes('UNIQUE')) fail('Unable to register with these details. Try signing in or contact an administrator.', 409);
        throw error;
      }
      await query(db, `INSERT INTO notifications(user_id,message,target_user_id) SELECT id,?,? FROM users WHERE role='admin' AND approved=1`, `New account waiting for approval: ${account.name}`, registration.meta.last_row_id).run();
      return json({ message: 'Registered. Wait for administrator approval before signing in.' }, 201);
    }
    if (path === 'login' && method === 'POST') {
      await rateLimit(db, request, 'login');
      const username = text(body.username, 'Username', 50);
      if (typeof body.password !== 'string' || !body.password.length || body.password.length > 128) fail('Enter your password.');
      const password = body.password;
      const user = await query(db, 'SELECT * FROM users WHERE username=?', username).first();
      // Use the same expensive derivation even when the username does not exist.
      const dummy = DUMMY_PASSWORD_HASH;
      const valid = await verifyPassword(password, user?.password || dummy);
      if (!valid || !user?.approved) fail('Login failed. Check your credentials and account approval.', 401);
      const token = randomToken();
      await query(db, 'DELETE FROM sessions WHERE expires < ?', now()).run();
      await query(db, 'INSERT INTO sessions(token_hash,user_id,expires) VALUES (?,?,?)', await digest(token), user.id, now() + 86400).run();
      const secure = new URL(request.url).protocol === 'https:' ? '; Secure' : '';
      return json({ user: publicUser(user) }, 200, { 'Set-Cookie': `sors_session=${token}; Path=/; HttpOnly; SameSite=Strict; Max-Age=86400${secure}` });
    }
    const user = await session(db, request);
    if (!user) fail('Please sign in with an approved account.', 401);
    if (path === 'me' && method === 'GET') return json({ user: publicUser(user) });
    if (path === 'profile' && method === 'POST') {
      const name = text(body.name, 'Name', 150);
      await query(db, 'UPDATE users SET name=? WHERE id=?', name, user.id).run();
      return json({ user: publicUser({ ...user, name }) });
    }
    if (path === 'profile/avatar' && method === 'POST') {
      if (!AVATARS.some(avatar => avatar.id === body.avatar)) fail('Choose a preset avatar.');
      await query(db, 'UPDATE users SET avatar=? WHERE id=?', body.avatar, user.id).run();
      return json({ user: publicUser({ ...user, avatar: body.avatar }) });
    }
    if (path === 'users' && method === 'GET') {
      const term = text(new URL(request.url).searchParams.get('q') || '', 'Search', 50);
      const { results } = await query(db, `SELECT id,name,username,role FROM users WHERE approved=1
        AND (instr(lower(username),lower(?))>0 OR instr(lower(name),lower(?))>0) ORDER BY name LIMIT 30`, term, term).all();
      return json({ users: results });
    }
    if (path === 'notifications' && method === 'GET') {
      const { results } = await query(db, `SELECT n.*,p.category,p.subject,p.audience,p.archived,t.approved AS target_approved FROM notifications n
        LEFT JOIN posts p ON p.id=n.post_id LEFT JOIN users t ON t.id=n.target_user_id WHERE n.user_id=? ORDER BY n.id DESC LIMIT 100`, user.id).all();
      const count = await query(db, 'SELECT COUNT(*) AS count FROM notifications WHERE user_id=? AND is_read=0', user.id).first();
      return json({ notifications: results, unread: count.count });
    }
    if (path === 'notifications/read' && method === 'POST') {
      if (body.id !== undefined && !Number.isInteger(body.id)) fail('Invalid notification.');
      await query(db, 'UPDATE notifications SET is_read=1 WHERE user_id=? AND (? IS NULL OR id=?)', user.id, body.id ?? null, body.id ?? null).run();
      return json({ message: 'Notifications marked read.' });
    }
    const removeComment = /^comments\/(\d+)\/remove$/.exec(path);
    if (removeComment && method === 'POST') {
      const comment = await query(db, 'SELECT id,user_id FROM comments WHERE id=? AND deleted=0', Number(removeComment[1])).first();
      if (!comment) fail('Comment not found.', 404);
      if (comment.user_id !== user.id && user.role !== 'admin') fail('You can only remove your own comments.', 403);
      await query(db, 'UPDATE comments SET deleted=1 WHERE id=?', comment.id).run();
      return json({ message: 'Comment removed from the discussion.' });
    }
    if (path === 'logout' && method === 'POST') {
      await query(db, 'DELETE FROM sessions WHERE token_hash=?', user.token_hash).run();
      return json({ message: 'Signed out.' }, 200, { 'Set-Cookie': 'sors_session=; Path=/; HttpOnly; SameSite=Strict; Max-Age=0' });
    }
    if (path === 'ranking' && method === 'GET') {
      const { results } = await db.prepare(`SELECT u.id,u.name,u.role,COUNT(p.id) AS posts
        FROM users u LEFT JOIN posts p ON p.user_id=u.id AND p.archived=0
        WHERE u.approved=1 GROUP BY u.id ORDER BY posts DESC,u.id LIMIT 100`).all();
      return json({ users: results });
    }
    if (path === 'archives' && method === 'GET') {
      if (!['teacher','admin'].includes(user.role)) fail('Teacher or administrator access required.', 403);
      const { results } = await db.prepare(`SELECT p.*,u.name FROM posts p JOIN users u ON u.id=p.user_id
        WHERE p.archived=1 ORDER BY p.id DESC LIMIT 100`).all();
      return json({ posts: results });
    }
    const restore = /^posts\/(\d+)\/restore$/.exec(path);
    if (restore && method === 'POST') {
      if (!['teacher','admin'].includes(user.role)) fail('Teacher or administrator access required.', 403);
      const post = await query(db, 'SELECT id FROM posts WHERE id=? AND archived=1', Number(restore[1])).first();
      if (!post) fail('Archived post not found.', 404);
      await query(db, 'UPDATE posts SET archived=0 WHERE id=?', post.id).run();
      return json({ message: 'Post restored.' });
    }
    if (path === 'posts' && method === 'GET') {
      const url = new URL(request.url);
      const category = url.searchParams.get('category') || '';
      const subject = url.searchParams.get('subject') || '';
      const audience = url.searchParams.get('audience') || '';
      const author = url.searchParams.get('author') || '';
      const post = url.searchParams.get('post') || '';
      const { results } = await query(db, `SELECT p.*, u.name, u.role,u.avatar,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id=p.id) AS likes,
        EXISTS(SELECT 1 FROM likes l WHERE l.post_id=p.id AND l.user_id=?) AS liked
        FROM posts p JOIN users u ON u.id=p.user_id WHERE (p.archived=0 OR (?<>'' AND (p.user_id=? OR ? IN ('admin','teacher'))))
        AND (?='' OR p.category=?) AND (?='' OR p.subject=?) AND (?='' OR p.audience=?)
        AND (?='' OR p.user_id=?)
        AND (?='' OR p.id=?)
        ORDER BY p.id DESC LIMIT 50`, user.id, post, user.id, user.role, category, category, subject, subject, audience, audience, author, author, post, post).all();
      return json({ posts: results });
    }
    if (path === 'posts' && method === 'POST') {
      if (!user.can_post) fail('Posting is disabled for your account.', 403);
      const content = text(body.content, 'Post', 5000);
      const resource = resourceLink(body.resource_url);
      const category = text(body.category, 'Category', 50);
      const subject = text(body.subject, 'Subject', 255);
      const catalogue = await import('../lib/catalogue.js');
      if (!catalogue.default[category]?.includes(subject)) fail('Select a valid subject.');
      if (!['general','professor'].includes(body.audience)) fail('Select an audience.');
      if (body.audience === 'professor' && user.role === 'student') fail('Only teachers and administrators can publish here.', 403);
      await query(db, 'INSERT INTO posts(user_id,content,resource_url,category,subject,audience) VALUES (?,?,?,?,?,?)', user.id, content, resource, category, subject, body.audience).run();
      return json({ message: 'Post published.' }, 201);
    }
    const match = /^posts\/(\d+)\/(like|comments|archive)$/.exec(path);
    if (match) {
      const id = Number(match[1]), action = match[2];
      const post = await query(db, 'SELECT * FROM posts WHERE id=? AND archived=0', id).first();
      if (!post) fail('Post not found.', 404);
      if (action === 'comments' && method === 'GET') {
        const { results } = await query(db, `SELECT c.id,c.user_id,c.content,c.created_at,u.name,u.avatar FROM comments c
          JOIN users u ON u.id=c.user_id WHERE c.post_id=? AND c.deleted=0 ORDER BY c.id LIMIT 100`, id).all();
        return json({ comments: results });
      }
      if (method === 'POST' && action === 'comments') {
        await query(db, 'INSERT INTO comments(post_id,user_id,content) VALUES (?,?,?)', id, user.id, text(body.content, 'Comment', 2000)).run();
        if (post.user_id !== user.id) await notify(db, post.user_id, `${user.name} commented on your post.`, id);
        return json({ message: 'Comment added.' }, 201);
      }
      if (method === 'POST' && action === 'like') {
        if (typeof body.liked !== 'boolean') fail('Specify like state.');
        const result = await query(db, body.liked ? 'INSERT OR IGNORE INTO likes(post_id,user_id) VALUES (?,?)' : 'DELETE FROM likes WHERE post_id=? AND user_id=?', id, user.id).run();
        if (body.liked && result.meta.changes && post.user_id !== user.id) await notify(db, post.user_id, `${user.name} liked your post.`, id);
        return json({ message: body.liked ? 'Liked.' : 'Like removed.' });
      }
      if (method === 'POST' && action === 'archive') {
        if (!['teacher','admin'].includes(user.role)) fail('Teacher or administrator access required.', 403);
        await query(db, 'UPDATE posts SET archived=1 WHERE id=?', id).run();
        if (post.user_id !== user.id) await notify(db, post.user_id, `${user.name} archived your post.`, id);
        return json({ message: 'Post archived.' });
      }
    }
    if (path.startsWith('admin/')) {
      if (user.role !== 'admin') fail('Administrator access required.', 403);
      if (path === 'admin/create-account' && method === 'POST') {
        if (!['student','teacher','admin'].includes(body.role)) fail('Choose a valid role.');
        const account = validateAccount({ ...body, role: body.role === 'admin' ? 'teacher' : body.role });
        const password = await hashPassword(account.password);
        try {
          await query(db, 'INSERT INTO users(username,email,name,password,role,approved) VALUES (?,?,?,?,?,1)', account.username, account.email, account.name, password, body.role).run();
        } catch (error) {
          if (String(error.message).includes('UNIQUE')) fail('Username or email is already registered.', 409);
          throw error;
        }
        return json({ message: 'Approved account created.' }, 201);
      }
      if (path === 'admin/stats' && method === 'GET') {
        const stats = await db.prepare(`SELECT (SELECT COUNT(*) FROM users WHERE approved=0) AS pending,
          (SELECT COUNT(*) FROM users) AS accounts,(SELECT COUNT(*) FROM posts WHERE archived=0) AS posts,
          (SELECT COUNT(*) FROM posts WHERE archived=1) AS archived`).first();
        return json({ stats });
      }
      if (path === 'admin/users' && method === 'GET') {
        const target = new URL(request.url).searchParams.get('account') || '';
        const { results } = await query(db, "SELECT id,username,name,role,approved,can_post FROM users WHERE (?='' OR id=?) ORDER BY approved,id DESC LIMIT 100", target, target).all();
        return json({ users: results });
      }
      if (path === 'admin/users' && method === 'POST') {
        if (!Number.isInteger(body.id) || body.id === user.id) fail('Choose another account.');
        if (typeof body.approved !== 'boolean' || typeof body.can_post !== 'boolean') fail('Specify approval and posting permission.');
        const target = await query(db, 'SELECT role,approved,can_post FROM users WHERE id=?', body.id).first();
        if (!target || target.role === 'admin') fail('Only non-administrator accounts can be changed here.', 403);
        await query(db, 'UPDATE users SET approved=?,can_post=? WHERE id=?', +body.approved, +body.can_post, body.id).run();
        if (!body.approved) await query(db, 'DELETE FROM sessions WHERE user_id=?', body.id).run();
        if (target.approved !== +body.approved) await notify(db, body.id, body.approved ? 'Your account is approved. You can now log in.' : 'Your account approval was revoked. Contact your administrator.');
        if (target.can_post !== +body.can_post) await notify(db, body.id, body.can_post ? 'Posting is enabled for your account.' : 'Posting is disabled for your account.');
        return json({ message: 'Account updated.' });
      }
    }
    fail('Endpoint not found.', 404);
  } catch (error) {
    if (!error.status) console.error('API failure', error.message);
    return json({ error: error.status ? error.message : 'Unable to finish the request. Please try again.' }, error.status || 500);
  }
}
