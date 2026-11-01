# URS SORS project map

## Navigation

`index.php` → `login.html` → `login.php`. Approved admins land on
`admin_home.php`; approved teachers/students land on `welcome.php`.
Welcome → `course_page.php` → `ce_subject_class_page.html` or
`cpe_subject_class_page.html` → category → subject → role-specific feed.
Subject URLs use `course_page.php?course=cpe_core&subject=Engineering+Economy`.
The subject page offers General and Professor Only feeds. Students can read
Professor Only resources but cannot post there.

## Every application file

| Files | Purpose |
|---|---|
| `index.php`, `login.html`, `login.php`, `logout.php` | Entry point, login form, bcrypt authentication/approval check, session logout |
| `register.html`, `register.php` | Student/teacher registration pending admin approval |
| `config.php` | Single database connection/settings source |
| `welcome.php` | Student/teacher welcome and resource navigation |
| `course_page.php`, `admin_course_page.php` | Degree catalogue; shared subject landing page and admin catalogue |
| `ce_subject_class_page.html`, `cpe_subject_class_page.html` | Degree category selectors |
| `ce_core.html`, `cpe_core.html` | Core engineering subject cards |
| `ce_elect.html`, `cpe_elect.html` | Elective subject cards |
| `ce_prof.html`, `cpe_prof.html` | Professional subject cards |
| `ce_gen_ed.html`, `cpe_gen_ed.html` | General education subject cards |
| `student_feed.php`, `teacher_feed.php`, `home.php` | Role-oriented feeds with hierarchy filters, posts, comments, attachments, likes and moderation |
| `feed.php` | Older general-purpose feed, retained as a reachable legacy view |
| `post_handler.php`, `newsfeed_post.php` | Form post creation and compatibility entry point |
| `add_post.php`, `get_posts.php` | JSON post creation/list endpoints |
| `add_comment.php`, `comment.php` | Comment/attachment creation and compatibility entry point |
| `like.php` | Like/unlike toggle and owner notification |
| `post_actions.php`, `delete_post.php` | Archive/restore; admin removal preserves posts by archiving |
| `delete_comment.php` | Owner/admin comment removal, preserving stored rows |
| `profile.php`, `admin_profile.php` | Profile display/uploads; admin profile also embeds the older feed |
| `upload_profile.php`, `upload_cover.php` | Validated image uploads and profile updates |
| `search.php` | Username search and profile links |
| `ranking_board.php`, `admin_ranking.php` | Post-count leaderboards, including zero-post users |
| `notifications.php`, `admin_notif.php` | Notification history and mark-as-read |
| `archived_posts.php`, `admin_archive.php` | Archived content and teacher/admin restoration |
| `admin_home.php` | Admin navigation hub |
| `admin_dashboard.php` | All-user listing |
| `manage_accounts.php` | Posting permission management |
| `admin_pendings.php` | Pending account approvals; rejection must not delete users |
| `admin_register.php` | Admin-created approved accounts |

## Assets and supporting files

`assets/css/` holds active styles: `admin.css` (admin navigation),
`home_sections_style.css` (catalogue), `login.css`, `register.css`,
`profile.css`, and `style.css` (feeds). `assets/images/` holds the original
five PNG assets and fallback profile/cover SVGs. The unreferenced
`admin_dashboard.css` and `index.css` are preserved in `_unused/assets/css/`;
the unreferenced older JSON feed client is `_unused/assets/js/script.js`.
Inline navigation scripts remain with their existing pages.

`includes/app.php` provides session, authorization, redirects, uploads and
notification helpers. `includes/subject.php` maps catalogue subjects to feed
filters. `uploads/` contains generated runtime files, not committed to git.
`Database/urs_sors.sql` is the original export, not a migration to run over a
populated database. `package.json`/`pnpm-lock.yaml` support the dev command.
The original distribution ZIP is preserved in `_unused/URS SORS.zip` rather
than deleted. No application page was removed as unused. `scripts/` holds
non-mutating lint/link checks, opt-in data-retaining integration tests, and
a CLI-only password setter. `docs/` contains this map, the repair summary,
and the latest integration request log. `Database/migrations/` contains the
non-destructive schema adjustment.
