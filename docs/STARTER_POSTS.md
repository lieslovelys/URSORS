# Starter resources

Run `/usr/bin/php8.3 scripts/seed_starter_posts.php` from the project root.
The CLI-only script uses `config.php`, looks up `admin001` by username, and
requires its role to be admin. The existing account is ALYSSA MAE ESPONILLA;
its name was not changed. No account IDs are hardcoded.

The inventory comes from the active `includes/catalogue.json`, not the content
dictionary. `subject_context()` supplies the same section, sub-section, and
canonical subject strings used by the site. Unknown content fails before any
inserts rather than silently skipping a new catalogue subject.

Every subject receives Welcome, Study Topics, and References in General, plus
Teaching Materials in Professor Only. References contain two published books
and one free official resource homepage. Shared subjects have separate CE/CPE
rows and program-specific application text. Posts have no attachments and
356 distinct timestamps spread across the preceding four weeks.

## Marker and cleanup

The seeder applies `Database/migrations/002_seed_marker.sql` using prepared
statements. It adds nullable `posts.seed_marker` and a unique index. Existing
rows retain NULL. Markers contain version, course, canonical-subject hash, and
post type, and are not included in displayed post content. A database advisory
lock serializes seed/cleanup operations; inserts are transactional.

`includes/views/ranking.php` now adds `p.seed_marker IS NULL` to its post join,
excluding starter posts from both student and admin ranking screens.
Apply the migration (or run the seeder) before deploying that ranking change.

To clean up, run:

```sh
/usr/bin/php8.3 scripts/remove_seed_posts.php
```

This archives only rows marked `seed:v1:%`; it never deletes posts. Rerunning
the seeder does not restore or duplicate archived seeds: its final active-feed
validation instead reports an error. Admins can restore them using the archive
screen if needed. Neither CLI script calls notification handlers.

## Verification in this workspace

Independent JSON parse and initial seed output:

```text
Independent catalogue count: 89
Subjects found: 89; expected posts: 356; created: 356; already present: 0
Total active seeded posts: 356
Missing or unexpected counts: none
```

| Program | Category | Subjects | General | Professor Only | Total |
|---|---|---:|---:|---:|---:|
| CE | gen_ed | 11 | 33 | 11 | 44 |
| CE | core | 11 | 33 | 11 | 44 |
| CE | prof | 14 | 42 | 14 | 56 |
| CE | elect | 7 | 21 | 7 | 28 |
| CPE | gen_ed | 11 | 33 | 11 | 44 |
| CPE | core | 10 | 30 | 10 | 40 |
| CPE | prof | 16 | 48 | 16 | 64 |
| CPE | elect | 9 | 27 | 9 | 36 |
| **Total** | | **89** | **267** | **89** | **356** |

Second run output:

```text
Subjects found: 89; expected posts: 356; created: 0; already present: 356
Total active seeded posts: 356
Missing or unexpected counts: none
```

Using a temporary PHP session for an existing approved student (no account or
password changes), curl opened both audience feeds for each following subject.
All 16 HTTP checks passed: exactly three starter posts in General, one in
Professor Only, each authored by ALYSSA MAE ESPONILLA, with no visible marker.
The temporary session was destroyed afterward.

| Group | Subject checked | General | Professor Only |
|---|---|---:|---:|
| cpe_elect | Advanced Web Development | 3 | 1 |
| ce_elect | Advanced Structural Design | 3 | 1 |
| cpe_prof | Capstone Design Project | 3 | 1 |
| ce_prof | Capstone Design Project | 3 | 1 |
| cpe_core | Engineering Drawing and Design | 3 | 1 |
| ce_core | Engineering Drawing and Design | 3 | 1 |
| cpe_gen_ed | Art Appreciation | 3 | 1 |
| ce_gen_ed | Art Appreciation | 3 | 1 |

Additional real verification output:

```text
PASS: existing posts rows unchanged (58)
PASS: existing users rows unchanged (34)
PASS: existing notifications rows unchanged (161)
PASS: existing comments rows unchanged (42)
PASS: existing likes rows unchanged (24)
PASS: no new users, notifications, comments, or likes
PASS ranking SQL + student HTTP: admin001 = 0 real posts; 356 seeded posts excluded
Seed summary: {"total":"356","markers":"356","timestamps":"356","oldest":"2026-10-04 08:46:37","newest":"2026-11-01 06:52:22"}
```

Existing row hashes were compared before/after, omitting only the newly added
NULL marker field from original posts. Cleanup was not run against the live
seeded feed so the resources remain available.

`pnpm test` output:

```text
PASS: 61 PHP files linted; 58 local links/assets resolved.
PASS: 89 subjects preserved; shared CSRF validation and friendly-error server settings present.
```

The existing PHP 8.3 development server was already listening on port 8000 and
was used for the HTTP checks. No database import, existing-row edits, commit,
push, checkout, or branch commands were performed.
