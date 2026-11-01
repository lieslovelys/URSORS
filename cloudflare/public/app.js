const $ = id => document.getElementById(id);
let user = null, catalogue = {}, routeVersion = 0, unread = 0;
const programs = { ce: 'Civil Engineering', cpe: 'Computer Engineering' };
const categories = { gen_ed: 'General Education Subjects', core: 'Core Engineering Subjects', prof: 'Professional Subjects', elect: 'Elective Subjects' };
const icons = {
  home:'M3 10 12 3l9 7v11h-6v-7H9v7H3z', subjects:'M12 5v16M3 3l9 2 9-2v16l-9 2-9-2z',
  ranking:'M8 3h8v7a4 4 0 0 1-8 0zM8 5H3v3a5 5 0 0 0 5 5m8-8h5v3a5 5 0 0 1-5 5M12 14v7m-5 0h10',
  profile:'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M4 21v-2a8 8 0 0 1 16 0v2',
  help:'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20M9 8a3 3 0 0 1 6 0c0 3-3 2-3 6m0 3v1',
  admin:'m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6zM8 12l3 3 5-6', archives:'M3 3h18v5H3zm2 5v13h14V8m-10 4h6',
  pending:'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M12 7v5l3 2',
  accounts:'M9 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6M2 21v-3a7 7 0 0 1 14 0v3M16 4a3 3 0 0 1 0 6m3 11v-3a7 7 0 0 0-2-5',
  notifications:'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
  'create-account':'M9 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6M2 21v-3a7 7 0 0 1 14 0v3m3-14v8m-4-4h8',
  logout:'M10 3H3v18h7m5-15 6 6-6 6M8 12h13'
};
function node(tag, text, cls) { const el = document.createElement(tag); if (text !== undefined) el.textContent = text; if (cls) el.className = cls; return el; }
function status(message = '') { $('status').textContent = message; $('status').hidden = !message; }
async function api(path, body) {
  const response = await fetch(`/api/${path}`, body === undefined ? {} : { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) });
  const data = await response.json();
  if (!response.ok) {
    if (response.status === 401 && user) { user = null; location.hash = '#login'; }
    throw new Error(data.error || 'Unable to finish the request.');
  }
  return data;
}
async function run(button, fn) {
  button.disabled = true;
  try { await fn(); } catch (error) { status(error.message); $('status').scrollIntoView({ block:'nearest' }); }
  finally { button.disabled = false; }
}
function action(label, fn, cls = 'secondary') {
  const button = node('button', label, cls); button.type='button';
  button.addEventListener('click', () => run(button, fn)); return button;
}
function confirmAction(title, description) {
  const dialog = $('confirm-dialog'); $('confirm-title').textContent = title; $('confirm-description').textContent = description;
  dialog.showModal();
  return new Promise(resolve => {
    const cleanup = result => { dialog.close(); dialog.removeEventListener('cancel', cancel); $('confirm-yes').onclick = null; $('confirm-cancel').onclick = null; resolve(result); };
    const cancel = event => { event.preventDefault(); cleanup(false); };
    $('confirm-yes').onclick = () => cleanup(true); $('confirm-cancel').onclick = () => cleanup(false); dialog.addEventListener('cancel', cancel);
  });
}
function nav(active) {
  const admin=user?.role==='admin';
  const items = user ? (admin ? [['home','Home'],['pending','Pending Accounts'],['accounts','All Accounts'],['notifications','Notifications'],['profile','Profile'],['subjects','Subjects'],['ranking','Ranking'],['admin','Admin'],['create-account','Create Account'],['archives','Archived Posts']] : [['home','Home'],['subjects','Subjects'],['notifications','Notifications'],['ranking','Ranking'],['profile','Profile']]) : [['login','Log in'],['register','Register']];
  if (user?.role==='teacher') items.push(['archives','Archived Posts']);
  items.push(['help','Help']);if(user)items.push(['logout','Logout']);
  const mobile=admin?['home','pending','accounts','notifications','profile']:['home','subjects','notifications','ranking','profile'];
  $('site-nav').replaceChildren(...items.map(([key,label]) => {
    const link = node('a'); link.href = '#'+key; link.className = mobile.includes(key) ? 'mobile-tab' : 'desktop-tab';
    if(key==='logout')link.addEventListener('click',event=>{event.preventDefault();run(link,logout);});
    if (key === active || (active === 'feed' && key === 'subjects')) link.setAttribute('aria-current','page');
    const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.setAttribute('viewBox','0 0 24 24'); svg.setAttribute('fill','none'); svg.setAttribute('stroke','currentColor'); svg.setAttribute('stroke-width','1.7'); svg.setAttribute('aria-hidden','true'); svg.classList.add('nav-icon');
    const path = document.createElementNS(svg.namespaceURI,'path'); path.setAttribute('d',icons[key] || icons.profile); svg.append(path);
    const labelEl=node('span',undefined,'tab-label');
    if(admin&&['pending','accounts'].includes(key)){labelEl.append(node('span',label,'desktop-label'),node('span',key==='pending'?'Pending':'Accounts','mobile-label'));}else labelEl.textContent=label;
    link.append(svg,labelEl);if(key==='notifications'&&unread)link.append(node('span',unread>99?'99+':String(unread),'badge'));return link;
  }));
  $('account').hidden = !user;
  if (user) { $('account-name').textContent=user.name; $('account-username').textContent='@'+user.username; $('account-role').textContent=user.role; }
}
function categoryName(key) { const [program,...rest] = key.split('_'); return `${programs[program] || program} · ${categories[rest.join('_')] || rest.join(' ')}`; }
function current() {
  const [page,query=''] = location.hash.slice(1).split('?'); return {page:page || (user?.role==='admin'?'admin':user?'home':'login'),params:new URLSearchParams(query)};
}
function feedFilter() { const {params} = current(); return {category:params.get('category') || '',subject:params.get('subject') || '',audience:params.get('audience') === 'professor' ? 'professor':'general'}; }
function feedHref(category,subject,audience='general') { return '#feed?'+new URLSearchParams({category,subject,audience}); }
function card(href,title,description,cta) { const el=node('a',undefined,'card'); el.href=href; el.append(node('strong',title),node('span',description),node('span',cta,'card-action')); return el; }
function subjectBrowser(params) {
  const root=$('subject-browser'); root.replaceChildren();
  const program=params.get('program'), category=params.get('category');
  if (!programs[program]) {
    const grid=node('div',undefined,'grid');
    for (const [key,label] of Object.entries(programs)) grid.append(card('#subjects?program='+key,label,'Browse subjects and shared learning resources.','Choose program →'));
    root.append(grid); return;
  }
  const crumbs=node('nav',undefined,'breadcrumbs'); crumbs.setAttribute('aria-label','Breadcrumb');
  const back=node('a','All programs'); back.href='#subjects'; crumbs.append(back,node('span',' / '+programs[program])); root.append(crumbs);
  if (!catalogue[category] || !category.startsWith(program+'_')) {
    root.append(node('h2',programs[program])); const grid=node('div',undefined,'grid');
    for (const key of Object.keys(catalogue).filter(k=>k.startsWith(program+'_'))) grid.append(card('#subjects?'+new URLSearchParams({program,category:key}),categories[key.slice(program.length+1)] || key,`${catalogue[key].length} subjects`,'View subjects →'));
    root.append(grid); return;
  }
  const backCategory=node('a','← Back to categories'); backCategory.href='#subjects?program='+program;
  root.append(backCategory,node('h2',categories[category.slice(program.length+1)] || category));
  const list=node('div',undefined,'subject-list');
  for (const subject of catalogue[category]) { const link=node('a',undefined,'subject-link'); link.href=feedHref(category,subject); link.append(node('span',subject),node('span','→')); list.append(link); }
  root.append(list);
}
function empty(title,description) { const el=node('div',undefined,'empty'); el.append(node('h2',title),node('p',description)); return el; }
function resourceLink(url) {
  const link=node('a',undefined,'resource-link'); link.href=url; link.target='_blank'; link.rel='noopener noreferrer';
  const copy=node('span'); copy.append(node('strong','Open resource ↗'),node('small',new URL(url).hostname)); link.append(node('span','↗'),copy); return link;
}
async function loadFeed(version=routeVersion) {
  const home=current().page==='home';
  const filters=home?{author:current().params.get('author')||''}:feedFilter();
  const {posts}=await api('posts?'+new URLSearchParams(filters));
  if (version !== routeVersion) return;
  const target=$(home?'community-feed':'feed');target.replaceChildren();if(!posts.length)target.append(empty('No resources here yet.','Browse Subjects to share a resource or start a discussion.'));
  for (const post of posts) {
    const article=node('article',undefined,'post');
    const author=node('div',undefined,'post-author'), image=node('img'); image.src='/assets/images/default_profile.svg'; image.alt='';
    const identity=node('div'); identity.append(node('strong',post.name),node('small',`${post.role} · ${post.created_at} UTC`)); author.append(image,identity);
    const tags=node('div',undefined,'post-tags');const subjectLink=node('a',post.subject,'badge');subjectLink.href=feedHref(post.category,post.subject,post.audience);tags.append(subjectLink,node('span',post.audience==='professor'?'Professor Only':'General','badge'));
    article.append(author,tags,node('p',post.content,'post-content')); if(post.resource_url) article.append(resourceLink(post.resource_url));
    const tools=node('div',undefined,'post-actions');
    const like=action(`${post.liked?'Liked':'Like'} · ${post.likes}`,async()=>{await api(`posts/${post.id}/like`,{liked:!post.liked}); await loadFeed();}); like.setAttribute('aria-pressed',!!post.liked); tools.append(like);
    const comments=node('section',undefined,'comments'); comments.hidden=true;
    async function loadComments() {
      const data=await api(`posts/${post.id}/comments`); comments.replaceChildren();
      for(const c of data.comments) { const el=node('div',undefined,'comment'); el.append(node('strong',c.name),node('p',c.content,'comment-content')); comments.append(el); }
      if(!data.comments.length) comments.append(node('p','No comments yet. Start the conversation.','hint'));
      const form=node('form',undefined,'comment-form'), label=node('label','Write a comment'), input=node('textarea'); input.required=true; input.maxLength=2000; label.append(input);
      const submit=node('button','Post comment'); submit.type='submit'; form.append(label,submit);
      form.addEventListener('submit',event=>{event.preventDefault(); run(submit,async()=>{await api(`posts/${post.id}/comments`,{content:input.value}); await loadComments(); status('Comment added.');});}); comments.append(form);
    }
    const commentButton=action('Comments',async()=>{comments.hidden=!comments.hidden; commentButton.setAttribute('aria-expanded',!comments.hidden); if(!comments.hidden) await loadComments();}); commentButton.setAttribute('aria-expanded','false'); tools.append(commentButton);
    if(['teacher','admin'].includes(user.role)) tools.append(action('Archive',async()=>{if(!await confirmAction('Archive this post?','It will leave the feed. You can restore it from Archived Posts.'))return; await api(`posts/${post.id}/archive`,{});await loadFeed();status('Post archived.');}));
    article.append(tools,comments); target.append(article);
  }
}
async function loadUsers(version=routeVersion) {
  const {users}=await api('admin/users'); if(version!==routeVersion)return;
  const selected=users.filter(account=>$('account-filter').value==='all'||!account.approved);
  $('users').replaceChildren(); if(!selected.length)$('users').append(empty('All caught up.','There are no accounts waiting for approval.'));
  for(const account of selected) {
    const panel=node('article',undefined,'panel'); const heading=node('div',undefined,'account-heading'); heading.append(node('h2',account.name),node('span',account.approved?'Approved':'Pending','badge'));
    panel.append(heading,node('p',`@${account.username} · ${account.role}`,'muted'));
    if(account.role!=='admin') {
      const tools=node('div',undefined,'account-tools');
      async function update(approved,can_post) { await api('admin/users',{id:account.id,approved:!!approved,can_post:!!can_post});await loadUsers();status('Account permissions updated.'); }
      tools.append(action(account.approved?'Revoke approval':'Approve account',async()=>{if(account.approved&&!await confirmAction('Revoke account approval?','This user will no longer be able to access the system.'))return;await update(!account.approved,account.can_post);},account.approved?'secondary':''),action(account.can_post?'Disable posting':'Enable posting',()=>update(account.approved,!account.can_post))); panel.append(tools);
    }
    $('users').append(panel);
  }
}
async function loadRanking(version) {
  const {users}=await api('ranking'); if(version!==routeVersion)return;
  $('ranking-list').replaceChildren();
  if(!users.length)$('ranking-list').append(empty('No contributions yet.','Share a resource in a subject to get started.'));
  users.forEach((account,index)=>{const row=node('div',undefined,'panel rank-row'), identity=node('div',undefined,'rank-person');identity.append(node('strong',account.name),node('small',account.role));row.append(node('span',String(index+1),'rank-place'),identity,node('span',`${account.posts} posts`,'badge'));$('ranking-list').append(row);});
}
async function loadArchives(version=routeVersion) {
  const {posts}=await api('archives');if(version!==routeVersion)return;
  $('archive-list').replaceChildren();if(!posts.length)$('archive-list').append(empty('No archived posts.','Posts archived by teachers and admins will appear here.'));
  for(const post of posts){const panel=node('article',undefined,'post');panel.append(node('h2',post.name),node('p',post.subject,'muted'),node('p',post.content,'post-content'),action('Restore to feed',async()=>{await api(`posts/${post.id}/restore`,{});await loadArchives();status('Post restored.');}));$('archive-list').append(panel);}
}
async function loadNotifications(version=routeVersion,render=true) {
  const data=await api('notifications');if(version!==routeVersion)return;
  unread=data.unread;nav(current().page);if(!render)return;
  $('notification-list').replaceChildren();if(!data.notifications.length)$('notification-list').append(empty('No notifications yet.','Account updates and activity on your posts will appear here.'));
  for(const notification of data.notifications){
    const panel=node('article',undefined,'panel');panel.append(node('strong',notification.message),node('p',notification.created_at+' UTC','hint'));const tools=node('div',undefined,'actions');
    if(notification.subject&&!notification.archived){const link=node('a','Open discussion','button secondary');link.href=feedHref(notification.category,notification.subject,notification.audience);tools.append(link);}
    if(!notification.is_read)tools.append(action('Mark as read',async()=>{await api('notifications/read',{id:notification.id});await loadNotifications();}));
    panel.append(tools);$('notification-list').append(panel);
  }
}
async function logout(){if(!await confirmAction('Log out?','You can log in again with your username and password.'))return;await api('logout',{});user=null;unread=0;for(const id of ['feed','community-feed','users','search-results','notification-list'])$(id).replaceChildren();location.hash='#login';}
async function route() {
  const version=++routeVersion; let {page,params}=current(); status();
  if(!user&&!['login','register','registered','help'].includes(page)){location.hash='#login';return;}
  if(user&&['login','register','registered'].includes(page)){location.hash=user.role==='admin'?'#admin':'#home';return;}
  const valid=['home','subjects','feed','profile','ranking','admin','pending','accounts','notifications','create-account','archives','login','register','registered','help'];
  if(!valid.includes(page)){location.hash=user?'#home':'#login';return;}
  if(['admin','pending','accounts','create-account'].includes(page)&&user?.role!=='admin'){location.hash='#home';return;}
  if(page==='archives'&&!['teacher','admin'].includes(user?.role)){location.hash='#home';return;}
  document.body.className=user?'with-sidebar':`auth-page auth-${page==='register'?'register':'login'}`;
  $('main').className='page'+(user?'':' narrow');$('auth').hidden=!!user||page==='help';$('workspace').hidden=!user||page==='help';$('help-screen').hidden=page!=='help';
  for(const name of ['login','register','registered'])$(name+'-screen').hidden=page!==name;
  document.querySelectorAll('[data-page]').forEach(el=>{el.hidden=el.dataset.page!==(page==='pending'?'accounts':page);});
  nav(page);const title={feed:'Resources',admin:'Admin Portal',accounts:'Account management',archives:'Archived Posts'}[page]||page[0].toUpperCase()+page.slice(1);$('mobile-title').textContent=title;document.title=title+' · URS SORS';
  if(user){
    $('profile-name').textContent=user.name;$('profile-input').value=user.name;$('profile-details').textContent=`@${user.username} · ${user.role} · ${user.can_post?'Posting enabled':'Posting disabled'}`;
    let more=$('profile-more');
    if(!more){more=node('section',undefined,'panel');more.id='profile-more';$('logout').before(more);}
    more.replaceChildren(node('h2','More options'));const links=node('div',undefined,'actions');
    const items=[['subjects','Browse Subjects'],['help','Help']];
    if(user.role==='admin')items.push(['admin','Admin'],['pending','Pending Accounts'],['accounts','All Accounts'],['create-account','Create Account']);
    items.push(['notifications','Notifications']);
    if(['teacher','admin'].includes(user.role))items.push(['archives','Archived Posts']);
    for(const [target,label] of items){const link=node('a',label,'button secondary');link.href='#'+target;links.append(link);}more.append(links);
  }
  try {
    if(user)await loadNotifications(version,page==='notifications');
    if(page==='home')await loadFeed(version);
    if(page==='subjects')subjectBrowser(params);
    if(page==='feed'){
      const filter=feedFilter();if(!catalogue[filter.category]?.includes(filter.subject)){location.hash='#subjects';return;}
      $('feed-title').textContent=filter.subject;$('feed-category').textContent=categoryName(filter.category);
      const allowed=user.can_post&&(filter.audience==='general'||user.role!=='student');$('publish').hidden=!allowed;
      $('feed-permission').textContent=!user.can_post?'Posting is disabled for your account. You can still read and discuss resources.':filter.audience==='professor'?'Everyone can read here. Only teachers and administrators can publish.':'Share subject-related resources and discussions with your community.';
      document.querySelectorAll('[data-audience]').forEach(el=>el.setAttribute('aria-pressed',el.dataset.audience===filter.audience));
      $('feed').replaceChildren(node('p','Loading resources…','hint'));await loadFeed(version);
    }
    if(page==='admin'){
      $('admin-greeting').textContent=`Welcome, ${user.name}!`;
      const {stats}=await api('admin/stats');if(version===routeVersion){let el=$('admin-stats');if(!el){el=node('p');el.id='admin-stats';$('admin-greeting').after(el);}el.textContent=`${stats.pending} pending · ${stats.accounts} accounts · ${stats.posts} active posts · ${stats.archived} archived`;}
    }
    if(['pending','accounts'].includes(page)){$('account-filter').value=page==='pending'?'pending':'all';await loadUsers(version);}
    if(page==='ranking')await loadRanking(version);
    if(page==='archives')await loadArchives(version);
  }catch(error){if(version===routeVersion)status(error.message);}
}
for(const path of ['login','register'])$(path).addEventListener('submit',event=>{
  event.preventDefault();const form=event.currentTarget;
  run(form.querySelector('button[type=submit]'),async()=>{const data=await api(path,Object.fromEntries(new FormData(form)));form.reset();if(data.user){user=data.user;location.hash=user.role==='admin'?'#admin':'#home';}else location.hash='#registered';});
});
$('publish').addEventListener('submit',event=>{event.preventDefault();run($('publish').querySelector('button[type=submit]'),async()=>{await api('posts',{...Object.fromEntries(new FormData($('publish'))),...feedFilter()});$('publish').reset();await loadFeed();status('Your resource was published.');});});
$('refresh').addEventListener('click',event=>run(event.currentTarget,()=>loadFeed()));
$('load-users').addEventListener('click',event=>run(event.currentTarget,()=>loadUsers()));
$('account-filter').addEventListener('change',()=>loadUsers().catch(error=>status(error.message)));
$('logout').addEventListener('click',event=>run(event.currentTarget,logout));
$('read-notifications').addEventListener('click',event=>run(event.currentTarget,async()=>{await api('notifications/read',{});await loadNotifications();}));
$('edit-profile').addEventListener('submit',event=>{event.preventDefault();run(event.currentTarget.querySelector('button[type=submit]'),async()=>{user=(await api('profile',Object.fromEntries(new FormData($('edit-profile'))))).user;await route();status('Profile updated.');});});
$('create-account').addEventListener('submit',event=>{event.preventDefault();run(event.currentTarget.querySelector('button[type=submit]'),async()=>{const body=Object.fromEntries(new FormData($('create-account')));if(body.role==='admin'&&!await confirmAction('Create administrator?','This account will have administrator access to accounts and moderation.'))return;await api('admin/create-account',body);$('create-account').reset();location.hash='#accounts';});});
$('account-search').addEventListener('submit',event=>{event.preventDefault();run(event.currentTarget.querySelector('button'),async()=>{const {users}=await api('users?'+new URLSearchParams(new FormData($('account-search'))));$('search-results').replaceChildren();if(!users.length)$('search-results').append(node('p','No matching approved accounts.'));for(const account of users){const link=node('a',undefined,'person-result');link.href='#home?author='+account.id;link.append(node('strong',account.name),node('span',`@${account.username} · ${account.role}`,'muted'),node('span','View posts →'));$('search-results').append(link);} });});
document.querySelectorAll('[data-password]').forEach(button=>button.addEventListener('click',()=>{const input=$(button.dataset.password);const show=input.type==='password';input.type=show?'text':'password';button.textContent=show?'Hide':'Show';button.setAttribute('aria-pressed',show);}));
document.querySelectorAll('[data-audience]').forEach(button=>button.addEventListener('click',()=>{const filter=feedFilter();location.hash=feedHref(filter.category,filter.subject,button.dataset.audience);}));
window.addEventListener('hashchange',()=>{route();window.scrollTo(0,0);});
try {
  const response=await fetch('/catalogue.json');if(!response.ok)throw new Error('Subject catalogue could not be loaded.');catalogue=await response.json();
  try {user=(await api('me')).user;}catch(error){if(error.message!=='Please sign in with an approved account.')status(error.message);}
  await route();
}catch(error){status(error.message);}
