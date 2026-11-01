# UX redesign — changes and handoff

## Before and after, by page

| Page(s) | Before | Now |
|---|---|---|
| All live pages | Repeated layouts, icon-only links, inconsistent menus | One logo/blue theme, readable type, labeled navigation, current-page highlight, keyboard focus, phone Menu, shared footer/Help |
| Login | Small form; plain error output | Visible labels, Show/Hide password, retained username, friendly inline wrong-password/pending-approval explanations |
| Registration | Technical response with no clear next step | Shorter necessary-field form, inline hints/validation, friendly errors, approval screen explaining what happens next |
| Welcome/Home | Mainly a welcome message and resource link | Name/role, prominent Browse Subjects, My Posts/Notifications/Ranking cards, dismissible Quick Guide |
| Subjects/program/category pages | Separate static pages with unclear transitions | Shared step-by-step cards, short explanations, subject search, breadcrumbs, preserved 89-subject catalogue |
| Subject audience page | General/Professor Only without enough explanation | Clear descriptions of who can read, post, and comment; obvious Open posts actions |
| Student/teacher/admin/general feeds | Four large duplicated templates and hidden permission behavior | One paginated feed, labeled Create Post, one file chooser, guidance, explicit posting restrictions, attachments, grouped comments, useful empty states |
| Post and comment actions | Little visible feedback | Success banners for share/comment/like/unlike/remove/archive/restore; Yes/Cancel dialogs before moderation/removal; direct post/reply links |
| Admin Home/Dashboard | Navigation hub or all-user table | Counted task cards, five clear jobs, short admin help box; the old dashboard route now opens this same hub |
| Pending Accounts | Sparse table and technical responses | Searchable readable cards with name/role/email, one-click approval, confirmed rejection, result banners |
| All Accounts | Wide table with permission toggles | Searchable mobile cards, status in words, Approve and Allow/Stop posting actions with feedback |
| Create Account | Admin form could be confusing | Same simple shared form with a clear “ready to use” explanation and redirect to account list |
| Archives | Repeated archive page | Shared readable post cards, explicit archive explanation, protected Restore controls, confirmation and feedback |
| Profile/admin profile | Separate templates; admin profile duplicated a feed | Same profile screen, photo choices/previews, size/type hints, own-account saves, clear My Posts and people-search links |
| Notifications | Older read-on-open behavior; no exact post destination | Unread styling, explicit Mark all as read POST button, actor names, new notifications link to the exact post; old messages keep fallback links |
| Ranking | Plain table | Mobile-friendly contribution list and “It is not grades” explanation; pagination |
| Search | Basic username view | Labeled name/username search linked from Profile |
| Help | No dedicated screen | Public short answers for students/teachers and admins, linked from every layout |
| Missing/error pages | Built-in errors/plain technical text | Friendly 404/request/500 pages with Home/Help; details logged privately |

## Preservation and structure

- Original root pages/helpers/checks are copied to `_unused/redesign_before/`.
  Replaced active CSS was moved to its `assets/css/` subfolder there. Root routes
  remain as small compatibility entry points; old HTML bookmarks redirect.
- Active UI: `assets/css/app.css` and `assets/js/app.js`. Original brand logo,
  blue color, and image files remain. No external font/icon CDN is required.
- Shared renderer/services are in `includes/` and `includes/views/`. See
  `PROJECT_MAP.md` for every route and helper.
- No database tables, columns, or migrations were added for this redesign.
  Notification post references use a `[post:N]` marker in the existing message;
  users see only the friendly text. Old messages cannot reliably identify an
  exact post, so their links open a related page.
- Quick Guide dismissal uses the browser/session, not a DB change.
- Original rows/files were not deleted. New test rows/files remain; fixture
  accounts are unapproved/posting-disabled and new fixture posts are archived.
  Original admin photo references are restored after upload tests. Existing
  historical `smoke_...` content from earlier work is not removed or altered.

## Safety and behavior changes

- CSRF tokens are validated for all POST forms, including login, registration,
  logout, uploads, marking notifications, and account/moderation actions.
  JSON/custom clients must use the same session/token contract.
- Logout is POST-only. Opening Notifications no longer marks anything read;
  choose Mark all as read. GET requests do not mutate these states.
- Sessions, bcrypt, approval/role checks, ownership checks, prepared statements,
  safe local return URLs, upload allowlists and soft-removal are preserved.
  Session cookies are HttpOnly/SameSite=Lax and strict session mode is enabled.
- Errors are hidden and logged in `var/log/app.log`. Development router blocks
  backups, DB exports, git files, support code and logs. Uploads require login.
- `pnpm dev` uses the router and matching 10 MB upload limits. Restart any old
  server after updating. Configure equivalent rules/limits on production servers;
  the PHP built-in server is not suitable for public production hosting.
- Registration does not yet send email, and there is no automatic password reset.
  UI/help explains this rather than promising either feature.

## Verification

- `pnpm test` after each major step: PHP lint, local references, catalogue and
  security-setting checks.
- Curl tests cover root PHP entry points for all roles, old bookmarks, protected
  paths, all 89 subjects/audience feeds, CSRF refusal, registration/duplicates,
  approval/rejection/session revocation, permission changes, uploads, posting,
  comments, likes, linked notifications/read state, moderation and logout.
- Chromium tests at 1280px and 360px exercise actual forms/cards, password toggle,
  menu/Escape, guide dismissal, uploads/previews, post/comment/like, confirmation
  cancel/accept, mobile admin search/approval/permission actions and logout.
- Axe-core automated A/AA checks cover public/shared/admin screens at both widths.
  This does not replace real Filipino-user usability testing, screen-reader
  testing, or checks on physical low-end phones. Reports are in
  `UX_TEST_RESULTS.md` and `ACCESSIBILITY_RESULTS.md`.
- Separate upload checks confirm a 3 MB file succeeds (above the old PHP default),
  downloaded bytes match, and an 11 MB file is rejected. A CLI-only synthetic
  exception verifies the friendly 500 page and private logging without adding
  a public debug endpoint. The deliberate diagnostic entry remains in the local
  log; final integration checks assert no *new* unexpected log entries.

## Decisions still useful (none blocks local use)

1. **Who should users contact?** Supply the real school admin/help email or office
   details. The current copy says “contact your school admin” without inventing one.
2. **Approval policy:** Confirm who may be approved as teacher/admin, and whether
   official school email/student-ID rules should be enforced. Current roles and
   approval behavior are preserved.
3. **Rejected accounts:** They stay unapproved and available for review because
   rows cannot be deleted and no new rejection-state column was introduced.
   Decide if a separate rejected-account workflow is needed later.
4. **Notifications/email/password recovery:** Decide whether to add email delivery
   and self-service password resets. New notifications link to posts; historical
   messages without IDs cannot be backfilled reliably.
5. **Dates and rollout:** Confirm the intended display timezone (for example,
   Asia/Manila), then arrange real student/teacher/admin testing on their phones.
   Existing server/database timestamp behavior is unchanged.
6. **Retained fixtures:** Testing accounts and archived test posts are intentionally
   kept. Decide whether to separate development fixtures from operational data
   in a future environment; do not approve test accounts for normal use.

No passwords were changed or committed as part of this redesign.
