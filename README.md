# URS SORS

PHP 8.3 + MariaDB student/teacher/admin resource feed.

## Run in Codespaces

```bash
cd /workspaces/URSORS
sudo service mariadb start
pnpm dev
```

Open forwarded port **8000** (or `http://localhost:8000`). `pnpm dev` uses
`/usr/bin/php8.3`; the default `php` binary lacks mysqli in this environment.
If port 8000 is already running, use that server rather than starting a second.

Database settings are only in `config.php`: `localhost`, database `urs_sors`,
user `root`, empty password. The existing database has already been migrated.
For another existing copy, apply the non-destructive migration once:

```bash
mariadb -uroot urs_sors < Database/migrations/001_preserve_content.sql
```

**Do not reimport `Database/urs_sors.sql` over an existing database.**
The development server needs write access to `uploads/`; subfolders are
created automatically. Uploads and local database changes are not stored in git.

## Checks

```bash
pnpm test
# Opt-in integration checks create labelled data and disable test users afterward.
ADMIN_PASSWORD='your current admin password' pnpm test:integration
```

To change the existing admin password without committing it:

```bash
ADMIN_PASSWORD='your chosen password' /usr/bin/php8.3 scripts/set_admin_password.php
```

See [project map](docs/PROJECT_MAP.md), [repair summary](docs/REPAIR_SUMMARY.md),
and [test results](docs/TEST_RESULTS.md).
