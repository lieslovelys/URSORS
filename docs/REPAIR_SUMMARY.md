# Repair summary

## Fixed

- Removed old XAMPP Admin/FrontEnd/BackEnd paths from live PHP/HTML/CSS/JS;
  corrected local styles, images, redirects, forms, and legacy feed filenames.
- Replaced 89 missing per-subject destinations with validated shared subject
  routes. Checked the archived original subject implementation before replacing
  it. Correct degree/category/subject/audience filters now reach role feeds.
- Fixed overwritten result sets in archives, dashboard, ranking, notifications,
  and admin home. Rankings include users with zero active posts. `All` no longer
  filters for a literal nonexistent section named All.
- Fixed likes so repeated requests toggle correctly. Added owner notifications
  for comments, likes and moderation, and admin notifications for registration.
  Notification history remains visible after marking read.
- Centralized session/auth checks and database settings. Login verifies approval
  and bcrypt, regenerates session ID, and redirects by role. Approval changes
  are checked on subsequent authenticated requests, not just at login.
- Validated registration, roles, duplicate usernames/emails, required fields,
  and password length. Admin-created accounts are immediately approved; public
  accounts require approval. Posting restrictions are enforced by handlers, not
  only by hidden forms. Students cannot post in Professor Only feeds.
- Fixed upload directories and paths; use random filenames, allowed extensions,
  image validation and a 10 MB cap. Profile pictures load from database rather
  than undefined session fields. Missing historical images use fallback assets.
  Search links display the requested profile, without allowing others' uploads.
- Fixed missing session values, nullable middle names, null filter values,
  unsafe external return redirects, duplicated admin notification menu markup,
  and attachment rendering. Shared include files reject direct web requests.
- Admin removal archives posts; comment removal marks a soft-delete flag;
  account rejection keeps the account unapproved. No existing rows were deleted.

## Schema/data changes

Applied `Database/migrations/001_preserve_content.sql`: widened `posts.section`,
`posts.sub_section`, and `posts.nested_sub_section` from 50 to 255 characters,
because real course names exceed 50; added `comments.is_deleted` for preserving
comments while supporting removal. No tables added, dropped, or recreated.
The original export is unchanged.

Updated only `admin001`'s password with PHP `password_hash(..., PASSWORD_BCRYPT)`
and verified real HTTP login. Plaintext credentials are not committed.
Student/teacher role tests use newly registered labelled accounts, not unknown
passwords or password resets on existing student/teacher accounts.

Integration runs retained labelled `smoke_...` users, posts, comments, likes,
notifications and uploads. All smoke-test accounts are now unapproved with
posting disabled. Retained test posts can appear in rankings/feeds; admins can
archive them if desired. No pre-existing rows were removed. Five original
comments refer to absent historical users/posts; they were left untouched.
The admin's profile/cover filenames were restored to their original export
values after upload tests; the original home-background cover image was copied
to its expected upload filename. Test upload files remain preserved.

## Moves

- Active styles → `assets/css/`.
- Original PNGs from `img/` → `assets/images/`; added fallback SVGs.
- Unreferenced `index.css`, `admin_dashboard.css` → `_unused/assets/css/`.
- Unreferenced `script.js` → `_unused/assets/js/script.js` (also made DOM-safe).
- Original distribution ZIP → `_unused/URS SORS.zip`.
- New common request/subject/registration helpers → `includes/`.
- Documentation → `docs/`; checks and password CLI → `scripts/`.

No original file was discarded. Existing uncommitted page edits were preserved
and incorporated where those same pages required repairs; existing pnpm launch
configuration was retained and extended with test commands.

## Remaining attention

- Browser-level visual, responsive, and JavaScript interaction review is still
  manual. Curl checks do not substitute for a browser or test external font/CDN
  availability. Original visual styles were retained.
- Existing teacher `teacher001` has posting disabled (`can_post=0`), unchanged.
  An admin can enable it under Manage Accounts if intended.
- Test data is intentionally retained and test accounts disabled. Do not
  reapprove test accounts for ordinary use.
- Codespaces/local development only: before public deployment, use dedicated
  DB credentials, HTTPS/session cookie hardening, CSRF protection, upload malware
  scanning, and a web-server document root/access rules that do not expose
  `Database/`, `_unused/`, `.git/`, or configuration/support files. The PHP built-in
  server is not a production server.
- Database/password/upload changes live in this Codespace, not git. Back up the
  database and `uploads/` before replacing the environment. No database dump
  containing new credentials was added to the repository.

## Running

See `README.md` for exact startup, migration and test commands.
