# URS SORS — Cloudflare preview

This is an isolated, incomplete replacement for the PHP application, not a production-ready deployment. The original application and its data are unchanged.

## Implemented

- Pending student/teacher registration, approved-account login and logout.
- PBKDF2-SHA256 password hashing (600,000 iterations), random hashed session tokens, one-day expiry, same-origin JSON mutations and login/registration throttling.
- Catalogue-based subject filters, General/Professor Only feeds, posts and HTTPS resource links.
- Comments, likes, contribution ranking, teacher/admin archive/restore and administrator approval/posting controls.
- Original branding, backgrounds and shared layout; separate login/register screens, home, guided program/category/subject navigation, profile summary and help. Mobile navigation includes additional tools through Profile.
- Admin sidebar follows the original order: Home, Pending Accounts, All Accounts, Notifications, Profile, Subjects, Ranking, Admin, Create Account, Archived Posts, Help, Logout. Home is a community feed with account search; Admin remains a separate landing/management page.
- Notifications for new registrations, approvals, posting permissions, likes, comments and archiving; unread counts and mark-as-read controls. Name editing and approved administrator-created accounts are supported.
- Server-side role/approval checks, prepared SQL statements and text-only rendering of user content.

## Not implemented yet

Comment moderation, account recovery, profile fields beyond display name, pagination beyond initial result limits, full design parity and migration of existing users/content/uploads. Direct uploads are intentionally excluded. Default UI does not load existing profile photos. Do not replace the production PHP application yet.

## Local development

Requires Node.js 22+.

```bash
cd cloudflare
npm ci
npm run db:local
npm test
npm run dev
```

Open the URL printed by Wrangler. Use HTTP only for localhost; production cookies are Secure under HTTPS. State lives in the ignored `.wrangler/` folder.

For the local API integration checks, start the server with `npm run dev -- --port 8788`, then run `python3 tests/integration.py` in a second terminal. These checks create labelled local test content and leave the test account unapproved with posting disabled. They never access remote D1.

Validation performed: four security unit tests passed; local Workers/D1 integration passed for pending login, role restrictions, cross-origin rejection, URL validation, posts, idempotent likes, comments, disabled posting and approval revocation. A Chromium browser flow passed for separate auth screens, password visibility, subject navigation, publishing, comments, archive/restore, ranking, logout, and no horizontal overflow at desktop/mobile widths. Desktop/mobile home screenshots were captured locally. Original PHP project checks also passed. Deployed Free-plan limits and a full accessibility review remain unverified.

Run browser checks with `npx playwright install chromium` and `npx playwright test`. Tests only use localhost and local D1; they create labelled accounts and disable them afterwards. Screenshots are saved under ignored `test-results/`.

### First administrator (controlled bootstrap)

1. Register your own account through the local website. Use a unique strong password.
2. As the trusted operator, inspect the local accounts:

```bash
npx wrangler d1 execute ursors --local --command "SELECT id,username,role,approved FROM users;"
```

3. Promote only your verified account, replacing `YOUR_ACCOUNT_ID` with its numeric ID:

```bash
npx wrangler d1 execute ursors --local --command "UPDATE users SET role='admin',approved=1 WHERE id=YOUR_ACCOUNT_ID;"
```

Never add a public administrator-registration endpoint. Passwords are never placed in this SQL or committed.

## Cloudflare setup — only after preview acceptance

No paid subscription or R2 is required for this links-only scope. Keep the repository private until existing SQL exports/history have had a privacy review.

1. Authenticate Wrangler with `npx wrangler login` on your own device. Do not send passwords/tokens in chat.
2. Create a D1 database with `npx wrangler d1 create ursors` and put its returned database ID into `wrangler.jsonc` (the ID is not a secret).
3. Review and apply schema with `npm run db:remote`. This command affects the real database; do not use it against an unrelated database.
4. Create a Pages project connected to the GitHub repository. Set root directory to `cloudflare`, build command to `npm ci`, and output directory to `public`. Functions remain outside the static output. Bind the production D1 database as `DB`; use a separate database for previews.
5. Verify configured bindings and authentication in a restricted preview before launch. Bootstrap the remote administrator using the same controlled process with `--remote` instead of `--local`, checking the account ID on that database first.
6. Verify all authorization, browser/mobile workflows, quotas and missing features before sharing the public link.

Never deploy the repository root: it contains database exports and PHP/support files. Only `public/` is static website content. No GitHub push, cloud resource creation or deployment has been performed by the local implementation steps.

PBKDF2 was exercised in local Workers runtime, but deployed Workers Free CPU behavior must be measured before launch. Never reduce password security to bypass quotas. Account recovery is still a launch blocker for real users.

External resource links are not private storage. Users must configure sharing permissions with the external provider. Links are opened by the browser; the API does not fetch them.
