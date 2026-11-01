import { AVATARS, avatarSrc } from './avatars.js';
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
  document.querySelector('.brand').href=user?'#home':'#landing';
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
  if (user) { $('account-name').textContent=user.name; $('account-username').textContent='@'+user.username; $('account-role').textContent=user.role;document.querySelector('#account img').src=avatarSrc(user.avatar); }
}
function categoryName(key) { const [program,...rest] = key.split('_'); return `${programs[program] || program} · ${categories[rest.join('_')] || rest.join(' ')}`; }
function profileSubjects() {
  const select=$('profile-subject');select.replaceChildren();
  for(const subject of catalogue[$('profile-category').value]||[]){const option=node('option',subject);option.value=subject;select.append(option);}
}
function prepareProfileComposer() {
  $('profile-publish').hidden=!user.can_post;$('profile-publish-restricted').hidden=!!user.can_post;
  $('profile-audience').querySelector('[value=professor]').disabled=user.role==='student';
  if(user.role==='student')$('profile-audience').value='general';
  if(!$('profile-category').options.length){
    for(const key of Object.keys(catalogue)){const option=node('option',categoryName(key));option.value=key;$('profile-category').append(option);}
    profileSubjects();
  }
}
function current() {
  const [page,query=''] = location.hash.slice(1).split('?'); return {page:page || (user?.role==='admin'?'admin':user?'home':'landing'),params:new URLSearchParams(query)};
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
  const profile=current().page==='profile';
  const filters=profile?{author:String(user.id)}:home?{author:current().params.get('author')||''}:{...feedFilter(),post:current().params.get('post')||''};
  const {posts}=await api('posts?'+new URLSearchParams(filters));
  if (version !== routeVersion) return;
  const target=$(profile?'profile-feed':home?'community-feed':'feed');target.replaceChildren();if(!posts.length)target.append(empty(profile?'You haven’t shared any posts yet.':'No resources here yet.','Browse Subjects to share a resource or start a discussion.'));
  for (const post of posts) {
    const article=node('article',undefined,'post');
    const author=node('div',undefined,'post-author'), image=node('img'); image.src=avatarSrc(post.avatar); image.alt='';
    const identity=node('div'); identity.append(node('strong',post.name),node('small',`${post.role} · ${post.created_at} UTC`)); author.append(image,identity);
    const tags=node('div',undefined,'post-tags');const subjectLink=node('a',post.subject,'badge');subjectLink.href=feedHref(post.category,post.subject,post.audience);tags.append(subjectLink,node('span',post.audience==='professor'?'Professor Only':'General','badge'));
    article.append(author,tags,node('p',post.content,'post-content')); if(post.resource_url) article.append(resourceLink(post.resource_url));
    if(post.archived){article.append(node('p','This post is archived and is no longer shown in the subject feed.','profile-note'));target.append(article);continue;}
    const tools=node('div',undefined,'post-actions');
    const like=action(`${post.liked?'Liked':'Like'} · ${post.likes}`,async()=>{await api(`posts/${post.id}/like`,{liked:!post.liked}); await loadFeed();}); like.setAttribute('aria-pressed',!!post.liked); tools.append(like);
    const comments=node('section',undefined,'comments'); comments.hidden=true;
    async function loadComments() {
      const data=await api(`posts/${post.id}/comments`); comments.replaceChildren();
      for(const c of data.comments) {
        const el=node('div',undefined,'comment');const author=node('div',undefined,'comment-author'),avatar=node('img');avatar.src=avatarSrc(c.avatar);avatar.alt='';avatar.width=30;avatar.height=30;author.append(avatar,node('strong',c.name));el.append(author,node('p',c.content,'comment-content'));
        if(c.user_id===user.id||user.role==='admin')el.append(action('Remove comment',async()=>{if(!await confirmAction('Remove this comment?','It will be hidden from the discussion.'))return;await api(`comments/${c.id}/remove`,{});await loadComments();status('Comment removed.');}));
        comments.append(el);
      }
      if(!data.comments.length) comments.append(node('p','No comments yet. Start the conversation.','hint'));
      const form=node('form',undefined,'comment-form'), label=node('label','Write a comment'), input=node('textarea'); input.required=true; input.maxLength=2000; label.append(input);
      const submit=node('button','Post comment'); submit.type='submit'; form.append(label,submit);
      form.addEventListener('submit',event=>{event.preventDefault(); run(submit,async()=>{await api(`posts/${post.id}/comments`,{content:input.value}); await loadComments(); status('Comment added.');});}); comments.append(form);
    }
    const commentButton=action('Comments',async()=>{comments.hidden=!comments.hidden; commentButton.setAttribute('aria-expanded',!comments.hidden); if(!comments.hidden) await loadComments();}); commentButton.setAttribute('aria-expanded','false'); tools.append(commentButton);
    if(['teacher','admin'].includes(user.role)) tools.append(action('Archive',async()=>{if(!await confirmAction('Archive this post?','It will leave the feed. You can restore it from Archived Posts.'))return; await api(`posts/${post.id}/archive`,{});await loadFeed();status('Post archived.');}));
    article.append(tools,comments); target.append(article);
    if(current().params.get('post')===String(post.id)){comments.hidden=false;commentButton.setAttribute('aria-expanded','true');await loadComments();}
  }
}
async function loadUsers(version=routeVersion) {
  const target=current().params.get('account');
  const {users}=await api('admin/users'+(target?'?'+new URLSearchParams({account:target}):'')); if(version!==routeVersion)return;
  const selected=users.filter(account=>$('account-filter').value==='all'||!account.approved);
  $('users').replaceChildren(); if(!selected.length)$('users').append(empty('All caught up.','There are no accounts waiting for approval.'));
  for(const account of selected) {
    const panel=node('article',undefined,'panel');if(target===String(account.id))panel.classList.add('notification-target');const heading=node('div',undefined,'account-heading'); heading.append(node('h2',account.name),node('span',account.approved?'Approved':'Pending','badge'));
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
    let href='#profile',label='View account update',symbol='✓';
    if(notification.post_id&&notification.subject){href=feedHref(notification.category,notification.subject,notification.audience)+'&post='+notification.post_id;label=notification.archived?'View archived post':'Open discussion';symbol='↗';}
    else if(user.role==='admin'&&(notification.target_user_id||notification.message.startsWith('New account waiting for approval:'))){href=(notification.target_approved?'#accounts':'#pending')+(notification.target_user_id?'?account='+notification.target_user_id:'');label='Review account';symbol='+';}
    const panel=node('article',undefined,'notification-card'+(notification.is_read?'':' is-unread'));
    const link=node('a',undefined,'notification-main');link.href=href;
    const icon=node('span',symbol,'notification-symbol');icon.setAttribute('aria-hidden','true');
    const copy=node('span',undefined,'notification-copy');copy.append(node('strong',notification.message),node('span',notification.created_at+' UTC','notification-time'),node('span',label+' →','notification-destination'));
    link.append(icon,copy);link.addEventListener('click',async event=>{
      if(event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
      event.preventDefault();try{await api('notifications/read',{id:notification.id});location.hash=href;}catch(error){status(error.message);}
    });panel.append(link);
    if(!notification.is_read){const dot=node('span',undefined,'notification-unread-dot');dot.setAttribute('aria-label','Unread');panel.append(dot);}
    $('notification-list').append(panel);
  }
}
async function logout(){if(!await confirmAction('Log out?','You can log in again with your username and password.'))return;await api('logout',{});user=null;unread=0;for(const id of ['feed','community-feed','users','search-results','notification-list'])$(id).replaceChildren();location.hash='#login';}
async function route() {
  setProfileEditing(false);
  const version=++routeVersion; let {page,params}=current(); status();
  if(!user&&!['landing','login','register','registered','help'].includes(page)){location.hash='#login';return;}
  if(user&&['landing','login','register','registered'].includes(page)){location.hash=user.role==='admin'?'#admin':'#home';return;}
  const valid=['landing','home','subjects','feed','profile','ranking','admin','pending','accounts','notifications','create-account','archives','login','register','registered','help'];
  if(!valid.includes(page)){location.hash=user?'#home':'#login';return;}
  if(['admin','pending','accounts','create-account'].includes(page)&&user?.role!=='admin'){location.hash='#home';return;}
  if(page==='archives'&&!['teacher','admin'].includes(user?.role)){location.hash='#home';return;}
  document.body.className=page==='landing'?'landing-page':user?'with-sidebar':`auth-page auth-${page==='register'?'register':'login'}`;
  $('landing-screen').hidden=page!=='landing';
  $('main').className='page'+(user||page==='landing'?'':' narrow');$('auth').hidden=!!user||['help','landing'].includes(page);$('workspace').hidden=!user||page==='help';$('help-screen').hidden=page!=='help';
  for(const name of ['login','register','registered'])$(name+'-screen').hidden=page!==name;
  document.querySelectorAll('[data-page]').forEach(el=>{el.hidden=el.dataset.page!==(page==='pending'?'accounts':page);});
  nav(page);const title={feed:'Resources',admin:'Admin Portal',pending:'Pending Accounts',accounts:'All Accounts','create-account':'Create Account',archives:'Archived Posts'}[page]||page[0].toUpperCase()+page.slice(1);$('mobile-title').textContent=title;document.title=title+' · URS SORS';
  if(user){
    $('profile-name').textContent=user.name;$('profile-input').value=user.name;$('profile-details').textContent=`@${user.username}`;
    document.querySelector('.profile-portrait').src=avatarSrc(user.avatar);
    $('profile-role').textContent=user.role==='admin'?'Administrator':user.role[0].toUpperCase()+user.role.slice(1);
    $('profile-username').textContent='@'+user.username;$('profile-posting').textContent=user.can_post?'Enabled':'Restricted';
  }
  try {
    if(user)await loadNotifications(version,page==='notifications');
    if(page==='home')await loadFeed(version);
    if(page==='profile'){prepareProfileComposer();await loadFeed(version);}
    if(page==='subjects')subjectBrowser(params);
    if(page==='feed'){
      const filter=feedFilter();if(!catalogue[filter.category]?.includes(filter.subject)){location.hash='#subjects';return;}
      $('feed-title').textContent=filter.subject;$('feed-category').textContent=categoryName(filter.category);
      const allowed=user.can_post&&(filter.audience==='general'||user.role!=='student');$('publish').hidden=!allowed||params.has('post');
      $('feed-permission').textContent=!user.can_post?'Posting is disabled for your account. You can still read and discuss resources.':filter.audience==='professor'?'Everyone can read here. Only teachers and administrators can publish.':'Share subject-related resources and discussions with your community.';
      document.querySelectorAll('[data-audience]').forEach(el=>el.setAttribute('aria-pressed',el.dataset.audience===filter.audience));
      $('feed').replaceChildren(node('p','Loading resources…','hint'));await loadFeed(version);
    }
    if(page==='admin'){
      $('admin-greeting').textContent=`Welcome, ${user.name}!`;
      const {stats}=await api('admin/stats');if(version===routeVersion){let el=$('admin-stats');if(!el){el=node('p');el.id='admin-stats';$('admin-greeting').after(el);}el.textContent=`${stats.pending} pending · ${stats.accounts} accounts · ${stats.posts} active posts · ${stats.archived} archived`;}
    }
    if(['pending','accounts'].includes(page)){
      $('account-filter').value=page==='pending'?'pending':'all';
      document.querySelector('[data-page=accounts] h1').textContent=page==='pending'?'Pending Accounts':'All Accounts';
      await loadUsers(version);
    }
    if(page==='ranking')await loadRanking(version);
    if(page==='archives')await loadArchives(version);
  }catch(error){if(version===routeVersion)status(error.message);}
}
for(const path of ['login','register'])$(path).addEventListener('submit',event=>{
  event.preventDefault();const form=event.currentTarget;
  run(form.querySelector('button[type=submit]'),async()=>{const data=await api(path,Object.fromEntries(new FormData(form)));form.reset();if(data.user){user=data.user;location.hash=user.role==='admin'?'#admin':'#home';}else location.hash='#registered';});
});
$('publish').addEventListener('submit',event=>{event.preventDefault();run($('publish').querySelector('button[type=submit]'),async()=>{await api('posts',{...Object.fromEntries(new FormData($('publish'))),...feedFilter()});$('publish').reset();await loadFeed();status('Your resource was published.');});});
$('profile-category').addEventListener('change',profileSubjects);
$('profile-publish').addEventListener('submit',event=>{
  event.preventDefault();const form=event.currentTarget;
  run(form.querySelector('button[type=submit]'),async()=>{
    await api('posts',Object.fromEntries(new FormData(form)));
    $('profile-content').value='';$('profile-resource').value='';
    await loadFeed();status('Your post was published to your profile and subject feed.');
  });
});
$('refresh').addEventListener('click',event=>run(event.currentTarget,()=>loadFeed()));
$('refresh-profile-posts').addEventListener('click',event=>run(event.currentTarget,()=>loadFeed()));
$('load-users').addEventListener('click',event=>run(event.currentTarget,()=>loadUsers()));
$('account-filter').addEventListener('change',()=>loadUsers().catch(error=>status(error.message)));
$('logout').addEventListener('click',event=>run(event.currentTarget,logout));
$('change-avatar').addEventListener('click',()=>{
  const options=$('avatar-options');options.replaceChildren();$('avatar-error').hidden=true;
  for(const avatar of AVATARS){
    const label=node('label',undefined,'avatar-option'),input=node('input');input.type='radio';input.name='avatar';input.value=avatar.id;input.required=true;input.checked=avatar.id===(user.avatar||'default');
    const image=node('img');image.src=avatar.src;image.alt='';image.width=64;image.height=64;label.append(input,image,node('span',avatar.label));options.append(label);
  }
  $('avatar-dialog').showModal();
});
$('cancel-avatar').addEventListener('click',()=>$('avatar-dialog').close());
$('avatar-form').addEventListener('submit',async event=>{
  event.preventDefault();const button=event.currentTarget.querySelector('button[type=submit]');button.disabled=true;$('avatar-error').hidden=true;
  try {
    const data=await api('profile/avatar',Object.fromEntries(new FormData($('avatar-form'))));user=data.user;
    document.querySelector('.profile-portrait').src=avatarSrc(user.avatar);nav(current().page);$('avatar-dialog').close();
    if(current().page==='profile')await loadFeed();status('Avatar updated.');
  }catch(error){$('avatar-error').textContent=error.message;$('avatar-error').hidden=false;}
  finally{button.disabled=false;}
});
function setProfileEditing(editing) {
  $('edit-profile').hidden=!editing;$('profile-name').hidden=editing;
  const button=document.querySelector('.profile-edit-link');button.hidden=editing;button.setAttribute('aria-expanded',editing);
  if(user)$('profile-input').value=user.name;
  if(editing)$('profile-input').focus();
}
document.querySelector('.profile-edit-link').addEventListener('click',()=>setProfileEditing(true));
$('cancel-profile-edit').addEventListener('click',()=>{setProfileEditing(false);document.querySelector('.profile-edit-link').focus();});
$('edit-profile').addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();setProfileEditing(false);document.querySelector('.profile-edit-link').focus();}});
$('read-notifications').addEventListener('click',event=>run(event.currentTarget,async()=>{await api('notifications/read',{});await loadNotifications();}));
$('edit-profile').addEventListener('submit',event=>{event.preventDefault();run(event.currentTarget.querySelector('button[type=submit]'),async()=>{user=(await api('profile',Object.fromEntries(new FormData($('edit-profile'))))).user;await route();status('Profile updated.');});});
$('create-account').addEventListener('submit',event=>{event.preventDefault();run(event.currentTarget.querySelector('button[type=submit]'),async()=>{const body=Object.fromEntries(new FormData($('create-account')));if(body.role==='admin'&&!await confirmAction('Create administrator?','This account will have administrator access to accounts and moderation.'))return;await api('admin/create-account',body);$('create-account').reset();location.hash='#accounts';});});
$('account-search').addEventListener('submit',event=>{event.preventDefault();run(event.currentTarget.querySelector('button'),async()=>{const {users}=await api('users?'+new URLSearchParams(new FormData($('account-search'))));$('search-results').replaceChildren();if(!users.length)$('search-results').append(node('p','No matching approved accounts.'));for(const account of users){const link=node('a',undefined,'person-result');link.href='#home?author='+account.id;link.append(node('strong',account.name),node('span',`@${account.username} · ${account.role}`,'muted'),node('span','View posts →'));$('search-results').append(link);} });});
document.querySelectorAll('[data-password]').forEach(button=>{
  const input=$(button.dataset.password);
  function updateIcon(){
    const visible=input.type==='text';
    const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');
    for(const [name,value] of Object.entries({viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'1.8','stroke-linecap':'round','stroke-linejoin':'round','aria-hidden':'true'}))svg.setAttribute(name,value);
    const path=document.createElementNS(svg.namespaceURI,'path');
    path.setAttribute('d',visible?'M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.2A11 11 0 0 1 12 5c7 0 10 7 10 7a18 18 0 0 1-3.2 4.2M6.2 6.2C3.4 8.1 2 12 2 12s3 7 10 7a11 11 0 0 0 5.8-1.8':'M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0');
    svg.append(path);button.replaceChildren(svg);button.setAttribute('aria-label',visible?'Hide password':'Show password');button.setAttribute('title',visible?'Hide password':'Show password');button.setAttribute('aria-pressed',visible);
  }
  updateIcon();button.addEventListener('click',()=>{input.type=input.type==='password'?'text':'password';updateIcon();});
});
document.querySelectorAll('[data-audience]').forEach(button=>button.addEventListener('click',()=>{const filter=feedFilter();location.hash=feedHref(filter.category,filter.subject,button.dataset.audience);}));
window.addEventListener('hashchange',()=>{route();window.scrollTo(0,0);});
try {
  const response=await fetch('/catalogue.json');if(!response.ok)throw new Error('Subject catalogue could not be loaded.');catalogue=await response.json();
  try {user=(await api('me')).user;}catch(error){if(error.message!=='Please sign in with an approved account.')status(error.message);}
  await route();
}catch(error){status(error.message);}
