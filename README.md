# URS SORS

URS SORS is a web-based academic resource-sharing and discussion system for
students, teachers, and administrators. It combines a social-style feed with
organized course and subject pages so users can share learning materials,
ask questions, and discuss academic topics in one place.

## What is the system for?

The system helps an academic community organize resources by degree program,
subject category, and individual subject instead of keeping everything in one
unstructured feed. Users can publish posts, attach images or documents, comment
on discussions, and like useful contributions.

The current catalogue covers **Civil Engineering** and **Computer Engineering**.
Each program groups its subjects into:

- General Education Subjects
- Core Engineering Subjects
- Professional Subjects
- Elective Subjects

Within a subject, users can open a **General** feed or a **Professor Only** feed.
Students can read Professor Only resources, but only teachers and administrators
can publish posts there.

## Who uses it?

| Role | What they can do |
|---|---|
| **Student** | Browse subjects, read resources, publish posts when permitted, upload attachments, comment, like posts, and manage their own profile. |
| **Teacher** | Share teaching resources, participate in discussions, publish in Professor Only feeds, and archive or restore posts. Posting permissions still apply. |
| **Administrator** | Approve accounts, create accounts, manage posting permissions, moderate content, restore archived posts, and access account listings and administrative pages. |

Public registration is available for students and teachers. New accounts must
be approved by an administrator before they can log in. Administrators can also
create approved accounts directly.

## Main features

- **Role-based login and account approval:** Users receive the appropriate
  welcome or admin home page after signing in with an approved account.
- **Course and subject navigation:** Resource feeds are filtered by program,
  category, subject, and audience.
- **Posts and attachments:** Users with posting permission can share text,
  images, and supported documents or other files.
- **Comments and likes:** Users can discuss posts, attach files to comments,
  and like or unlike contributions.
- **Notifications:** Users receive updates about comments, likes, moderation,
  and account approval. Administrators are notified about new registrations.
- **Profiles and user search:** Users can update their profile picture and cover
  photo, search for usernames, and view other users' profiles.
- **Ranking board:** Users are ranked by their number of non-archived posts.
  This measures contribution count, not grades or academic performance.
- **Archives and moderation:** Teachers and administrators can archive and
  restore posts. Admin post removal preserves the post by archiving it;
  comment removal hides the comment without deleting its stored row.
- **Posting permission management:** Administrators can enable or restrict a
  user's ability to publish posts.

## How the system works

1. **Register:** A student or teacher submits their account details.
2. **Approve:** An administrator reviews the pending account and approves it.
3. **Log in:** Students and teachers reach the welcome page; administrators
   reach the admin home page.
4. **Choose a subject:** Open Resources, choose Civil Engineering or Computer
   Engineering, select a subject category, and then select a subject.
5. **Open a feed:** Choose General or Professor Only to view its resources.
6. **Participate:** Read posts, download attachments, comment, like contributions,
   or publish a post when permitted.
7. **Stay updated:** Check notifications, visit profiles, and view the ranking
   board. Teachers and administrators can moderate posts when necessary.

## A simple experience on phones and computers

Every screen uses the same labeled menu: **Home, Subjects, Notifications,
Ranking, Profile, Help, and Logout**. Administrators also have **Admin**.
On a phone, choose **Menu** to open these links. The current page is highlighted.

Home offers **Browse Subjects**, **See My Posts**, **Notifications**, and
**Ranking**, plus a Quick Guide you can hide. Subject cards explain each step,
and breadcrumbs let you go back without starting over. Posts, comments,
uploads, approvals, and other actions show a clear result message.

Forms have visible labels, password show/hide controls, and friendly hints.
Photos can be previewed before saving. Admin account lists use readable cards,
not wide tables. Destructive actions ask for confirmation with **Yes, continue**
and **Cancel**. The logo and blue brand color are retained.

See the [simple User Guide](docs/USER_GUIDE.md) or open **Help** inside the site.
The [redesign summary](docs/UX_REDESIGN.md) explains each page's before/after
changes and the remaining product decisions.

## Technology and project organization

The application uses **PHP 8.3**, **MariaDB**, **HTML**, **CSS**, and
**JavaScript**. PHP sessions maintain login state, mysqli connects to the
database, and passwords are stored as bcrypt hashes. pnpm provides convenient
development and test commands; the website itself runs on PHP.

The database stores accounts in `users`, shared content in `posts`, discussions
in `comments`, reactions in `likes`, and updates in `notifications`.

| Location | Contents |
|---|---|
| Root PHP files | Small route entry points and protected action handlers |
| Root HTML files | Compatibility redirects for older bookmarks |
| `router.php` | Friendly 404 pages, old-link redirects, protected support folders, and authenticated downloads |
| `config.php` | Shared database connection settings |
| `includes/` | Shared bootstrap, layout, views, flash messages, CSRF, authorization, registration, uploads, and subject catalogue |
| `assets/` | One main stylesheet (`css/app.css`), shared behavior (`js/app.js`), and brand images |
| `uploads/` | Uploaded profile images, cover photos, and attachments; excluded from git |
| `Database/` | Original SQL export and non-destructive migrations |
| `scripts/` | Project checks, integration tests, and the CLI admin-password tool |
| `docs/` | Detailed file map, repair notes, and test results |
| `_unused/` | Preserved older assets and the original project ZIP |

## Current scope

URS SORS focuses on resource sharing and academic discussions. It is not a
grading, enrollment, or attendance-management system. The current setup is for
local development and GitHub Codespaces; production deployment requires
additional security and web-server configuration, as described in the
[repair summary](docs/REPAIR_SUMMARY.md).

## Run in Codespaces

```bash
cd /workspaces/URSORS
sudo service mariadb start
pnpm dev
```

Open forwarded port **8000** (or `http://localhost:8000`). `pnpm dev` uses
`/usr/bin/php8.3`; the default `php` binary lacks mysqli in this environment.
If an older server is already running, stop it and restart `pnpm dev` so it uses
the new `router.php`. Do not start a second server on the same port. The router
is required for friendly missing-page responses and protected support folders.

Database settings are only in `config.php`: `localhost`, database `urs_sors`,
user `root`, empty password. The existing database has already been migrated.
For another existing copy, apply the non-destructive migration once:

```bash
mariadb -uroot urs_sors < Database/migrations/001_preserve_content.sql
```

**Do not reimport `Database/urs_sors.sql` over an existing database.**
The development server needs write access to `uploads/` and `var/log/`;
subfolders are created automatically. Uploads and local database changes are
not stored in git. Each file can be up to **10 MB**; the dev command sets PHP's
upload limits to match. Raw PHP errors are hidden; details are logged in
`var/log/app.log` (excluded from git and blocked by the router).

The UX redesign requires **no new migration, columns, or tables**. The migration
above belongs to the earlier technical repair, not this redesign.

## Checks

```bash
pnpm test
# Opt-in curl checks create labelled data; users are disabled and posts archived afterward.
ADMIN_PASSWORD='your current admin password' pnpm test:integration

# Optional real-browser checks (desktop and 360px mobile).
python3 -m pip install --user -r requirements-dev.txt
python3 -m playwright install --with-deps chromium
ADMIN_PASSWORD='your current admin password' WITH_BROWSER=1 pnpm test:integration

# Optional automated accessibility checks; download axe-core only for testing.
curl -fsSL https://cdnjs.cloudflare.com/ajax/libs/axe-core/4.10.3/axe.min.js -o /tmp/opencode/axe.min.js
AXE_SCRIPT=/tmp/opencode/axe.min.js ADMIN_PASSWORD='your current admin password' python3 scripts/accessibility_check.py
```

To change the existing admin password without committing it:

```bash
ADMIN_PASSWORD='your chosen password' /usr/bin/php8.3 scripts/set_admin_password.php
```

See [project map](docs/PROJECT_MAP.md), [repair summary](docs/REPAIR_SUMMARY.md),
and [UX test results](docs/UX_TEST_RESULTS.md). The earlier
[technical test report](docs/TEST_RESULTS.md) is retained as historical context.

All state-changing forms, including login, registration, and logout, require a
session-bound CSRF token. Older custom API clients must first obtain a token
from a page in the same session and send it as `csrf_token` or `X-CSRF-Token`.
Logout is now POST-only. Notification read state changes only when the user
chooses **Mark all as read**, not simply by opening the page.
