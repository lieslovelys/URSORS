# Cloudflare migration plan

Status: an isolated initial preview exists in `cloudflare/`; the existing PHP application is unchanged and no Cloudflare deployment exists yet. See `cloudflare/README.md` for implemented features, missing features and local setup.

## Agreed scope

- Keep source code on GitHub; the repository can remain private.
- Use Cloudflare Pages for the frontend and same-origin Pages Functions (Workers runtime) for the API.
- Use D1 for relational data instead of MariaDB. Stay on free plans without enabling paid subscriptions.
- Use external HTTPS resource links instead of direct uploads. Do not enable R2.
- Keep existing styling, branding, course catalogue, roles and account-approval behavior.
- Free services have quotas and availability policies; neither a permanent link nor unlimited free usage is guaranteed.

## Isolated implementation

Build the new application under `cloudflare/`, with only its generated public assets published. Never select the repository root as the Pages output directory: it contains SQL exports and support files that must not be served publicly.

Keep the PHP application, local database and uploads intact. Back up the current database and uploads privately before any data migration. Do not automatically import the historical SQL export or test accounts.

Proposed layout:

```text
cloudflare/
  public/           # Static frontend and approved public assets only
  functions/api/   # Server-side authenticated endpoints
  migrations/      # Schema-only D1 migrations, no personal data
  tests/           # API authorization and workflow tests
  wrangler.jsonc   # Local/deployment configuration, no secrets
```

## Delivery stages

1. Foundation: isolated build, local D1 schema, shared UI, configuration instructions.
2. Authentication: registration, pending approval, login/logout, secure sessions and administrator bootstrap.
3. Resources: catalogue, filtered feeds, posts and external attachment links.
4. Participation: comments, likes, notifications, profiles and ranking.
5. Administration: approvals, posting permissions, archive/restore and moderation.
6. Tests and security review, then explicitly authorized GitHub push and Cloudflare deployment.

Each stage must distinguish implemented features from placeholders. Do not publish an incomplete app as a functioning replacement.

## Backend and security requirements

- Reimplement PHP behavior in JavaScript/TypeScript; Pages does not execute the current PHP routes.
- Convert MySQL schema and queries to D1/SQLite and use parameterized queries.
- Enforce approval, role, ownership, posting permission and Professor Only restrictions on the server for every applicable action.
- Use a reviewed authentication approach compatible with Workers Free CPU limits. Benchmark password hashing; never weaken password security merely to fit a quota.
- Store sessions securely with expiry and revocation; cookies must be HttpOnly, Secure and SameSite. Protect state-changing requests against CSRF.
- Prevent account enumeration where practical and rate-limit registration and login. Plan account recovery before production use.
- Escape user-generated text; never insert post/comment content as raw HTML.
- Validate attachment links as HTTPS URLs with a length limit; reject credentials and executable URL schemes. Do not fetch arbitrary user links server-side. Open external links with `rel="noopener noreferrer"` and explain that access depends on the external provider's sharing settings.
- Profile and cover uploads are unavailable initially; use bundled default images. Existing local uploads remain preserved but are not automatically public.
- Keep personal data, password hashes, database exports, session secrets and tokens out of static assets and public Git history. Review existing tracked exports before making the repository public; ignoring a file does not remove it from history.
- Create the first administrator through a controlled server-side/bootstrap process, never through public role selection or a hard-coded password.

## Data migration decisions

Default to a clean deployment until the owner explicitly chooses which existing accounts/content to migrate. Existing bcrypt credentials are not assumed to be compatible with the new authentication implementation. Preserve ownership mappings if content is migrated, and document any secure account-reset process.

## Acceptance checks before launch

- Pending users cannot access protected data, including through direct API requests.
- Students cannot post to Professor Only feeds or grant themselves elevated roles.
- Restricted users cannot post; users cannot edit others' accounts or content.
- Session expiry/logout, CSRF, safe rendering and invalid URLs are tested.
- Likes remain unique per user/post; archives and soft deletion preserve intended behavior.
- Course filters, notifications, ranking and external resource links work on mobile and desktop.
- Published files contain no PHP sources, SQL exports, archives, credentials or private uploads.
- Verify current Pages, Workers and D1 free quotas before deployment; document quota failure behavior.

## Account setup needed for deployment

The owner needs a Cloudflare account, a Pages project connected to the chosen GitHub repository/branch, and a D1 database binding. No R2 subscription is needed for this scope. Do not post account passwords or API tokens in chat or commit them to Git.

References:

- https://developers.cloudflare.com/pages/
- https://developers.cloudflare.com/pages/functions/
- https://developers.cloudflare.com/workers/platform/limits/
- https://developers.cloudflare.com/d1/
- https://developers.cloudflare.com/r2/get-started/
