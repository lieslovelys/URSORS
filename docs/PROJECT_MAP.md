# URS SORS project map

## Request flow

`pnpm dev` starts PHP 8.3 on port 8000 with `router.php`.

1. The router redirects old `.html` bookmarks, serves public brand assets,
   protects source/support folders, and checks login before serving uploads.
2. A root page selects a screen in `includes/pages.php`.
3. `includes/bootstrap.php` starts shared services, hides/logs errors, sets
   response headers, loads the one DB configuration, and validates POST CSRF.
4. The dispatcher checks login/admin access before loading the shared view.
5. Views use the same layout, navigation, footer, field helpers, flash messages,
   and confirmation dialog. Mutating forms POST to protected handlers and
   redirect with a plain-language result banner.

## Navigation and subjects

`index.php` → `login.php` → `welcome.php` (student/teacher) or
`admin_home.php` (admin). The Home navigation item opens the same simple
welcome/actions screen for all roles; Admin opens the admin task dashboard.

`course_page.php` → `?program=ce|cpe` → `?course=ce_core` (or another group)
→ `?course=ce_core&subject=Engineering+Economy` → General / Professor Only feed.

The eight groups and all 89 original subjects are in `includes/catalogue.json`.
`includes/catalogue.php` maps card names to the original database hierarchy.
Breadcrumbs show the selected program, group, subject, and audience. Students
can read/comment in Professor Only feeds but cannot publish posts there.

## Root pages and handlers

| Files | Shared destination / responsibility |
|---|---|
| `index.php` | Send visitors to login or Home |
| `login.php`, `register.php`, `admin_register.php` | `views/auth.php`: labeled forms, password controls, validation, approval explanation; admin creation is protected |
| `login.html`, `register.html` | Old bookmarks redirect to their PHP screens |
| `logout.php` | CSRF-protected POST logout with confirmation banner |
| `welcome.php` | `views/home.php`: role/name, Browse Subjects, My Posts, notifications, ranking, dismissible Quick Guide |
| `course_page.php`, `admin_course_page.php` | `views/catalogue.php`: program/group/subject/audience cards and subject search |
| `ce_subject_class_page.html`, `cpe_subject_class_page.html` | Compatibility redirects to the program selector |
| `ce_core.html`, `ce_elect.html`, `ce_prof.html`, `ce_gen_ed.html`, corresponding `cpe_*.html` | Compatibility redirects to the correct group |
| `feed.php`, `home.php`, `student_feed.php`, `teacher_feed.php` | `views/feed.php`: shared paginated feed, composer, contextual permissions, My Posts (`mine=1`), single post (`post_id=N`) |
| `archived_posts.php`, `admin_archive.php` | Same feed view in archive mode; restore controls only for teachers/admins |
| `profile.php`, `admin_profile.php` | `views/profile.php`: profile photos, previews, own uploads; no duplicate embedded feed |
| `search.php` | `views/search.php`: name/username search linked from Profile |
| `notifications.php`, `admin_notif.php` | `views/notifications.php`: unread styling, related-post links, explicit POST Mark all as read |
| `ranking_board.php`, `admin_ranking.php` | `views/ranking.php`: paginated contribution list and not-grades explanation |
| `admin_home.php`, `admin_dashboard.php` | `views/admin.php`: counted task cards and short admin help |
| `manage_accounts.php`, `admin_pendings.php` | Same admin view: searchable mobile-friendly accounts, approval/rejection/posting actions |
| `post_handler.php`, `newsfeed_post.php` | Normal form post creation and compatibility alias |
| `add_post.php`, `get_posts.php` | JSON creation/list endpoints; POST creation requires CSRF |
| `add_comment.php`, `comment.php` | Text/file comment creation and compatibility alias |
| `like.php` | Like/unlike toggle with feedback and linked owner notification |
| `post_actions.php`, `delete_post.php` | Archive/restore; admin removal archives instead of deleting |
| `delete_comment.php` | Owner/admin soft-removal using existing `is_deleted` |
| `upload_profile.php`, `upload_cover.php` | Validated own-account image uploads and feedback |
| `help.php` | Shared-layout, public help for students/teachers and admins |
| `config.php` | The only DB connection/settings source; direct HTTP access blocked |
| `router.php` | Routing, safe static serving/downloads, friendly 404, source-folder protection |

## Shared implementation

- `includes/app.php`: sessions, login/admin/post checks, safe return redirects,
  uploads, notifications, friendly request errors.
- `includes/bootstrap.php`: CSRF validation, error logging/exception page,
  shared security headers, layout and DB loading.
- `includes/layout.php`: header/nav/footer, flash banners, cards, labeled fields,
  CSRF fields and shared confirmation dialog.
- `includes/pages.php`: screen dispatcher and access guards.
- `includes/post_components.php`: one post/comment/attachment renderer used by
  all feeds and archives.
- `includes/register_account.php`: server-side field validation and bcrypt
  registration service. Optional middle names remain supported by the database;
  the simpler form asks only for first/last name.
- `includes/catalogue.json`, `includes/catalogue.php`: canonical catalogue and
  subject hierarchy helpers. `includes/subject.php` retains a compatibility helper.
- `includes/views/`: `auth.php`, `home.php`, `catalogue.php`, `feed.php`,
  `admin.php`, `profile.php`, `notifications.php`, `ranking.php`, `search.php`.

## Assets, preserved files, and runtime data

`assets/css/app.css` is the only active stylesheet; `assets/js/app.js` handles
mobile navigation, password visibility, inline validation, photo previews, and
Yes/Cancel confirmations. No font/icon CDN is needed by the UI. Existing logo,
background PNGs, and default image SVGs remain in `assets/images/`.

The pre-redesign root pages, helpers, checks and old styles are preserved under
`_unused/redesign_before/`. Older distribution files remain in `_unused/`.
Nothing was discarded. Those folders are not HTTP-accessible through the router.

`uploads/` and `var/log/app.log` are runtime data, excluded from git. New related
notifications append a `[post:N]` marker to the existing message field; the UI
removes the marker when displaying text. Older messages without a post reference
still work with a related-page fallback. No schema change is required.

`Database/urs_sors.sql` is the original export. `Database/migrations/` contains
the earlier technical repair; do not reimport or run a new migration for the UX
redesign. The Quick Guide dismissal is stored in the session and a per-user,
HttpOnly, SameSite browser cookie, not a new database column.

## Checks and documentation

- `scripts/check_project.py`: PHP lint, local references, catalogue retention,
  and shared CSRF/error-setting checks (`pnpm test`).
- `scripts/smoke_test.py`: compatibility launcher for `ux_integration.py`.
- `scripts/ux_integration.py`: opt-in curl tests plus optional Chromium role
  workflows at 1280px and 360px. Fixtures are retained, users disabled, new posts
  archived, and original admin photos restored even when assertions fail.
- `scripts/accessibility_check.py`: optional read-only axe/Chromium checks with
  a locally supplied axe-core script.
- `scripts/set_admin_password.php`: existing CLI-only password setter.
- `requirements-dev.txt`: optional Playwright test dependency.
- `docs/USER_GUIDE.md`, `UX_REDESIGN.md`, `UX_TEST_RESULTS.md`,
  `ACCESSIBILITY_RESULTS.md`: current UX guide, changes, decisions and evidence.
- `docs/REPAIR_SUMMARY.md`, `TEST_RESULTS.md`: historical technical repair notes.
