# Changelog

This changelog documents **only the two most recent published GitHub releases** of `mod_status`. Each entry is grounded in the actual Git comparison with its immediately preceding **published release** tag. Commits made after release `1.1.6` are excluded.

## [1.1.6] - 2026-10-15

Published: 2026-10-15 21:21:09 UTC.

Comparison: [1.1.5...1.1.6](https://github.com/EduardoKrausME/moodle-mod_status/compare/1.1.5...1.1.6)

### Plugin icon assets

- Added a PNG plugin icon at `pix/icon.png`.
- Renamed the existing SVG icon from `pix/icon.svg` to `pix/monologo.svg`, with no changes to the SVG's contents in the published-tag comparison.

### Release metadata

- Updated `version.php` from numeric plugin version `2026101500` / release `1.1.5` to `2026101501` / release `1.1.6`.
- The required Moodle build remains `2024100700`; the component remains `mod_status` and maturity remains stable.

### Scope

This published-tag comparison contains two commits and changes only `pix/icon.png`, the renamed SVG icon and `version.php`. No activity runtime code, business logic, user-facing behavior or tests changed between these tags.

## [1.1.5] - 2026-10-15

Published: 2026-10-15 18:48:31 UTC.

Comparison: [1.1.4...1.1.5](https://github.com/EduardoKrausME/moodle-mod_status/compare/1.1.4...1.1.5)

### Continuous integration

- Updated the PostgreSQL service image used by the GitHub Actions CI workflow from `postgres:16` to **`postgres:17`**.
- Updated the MariaDB CI service image from `mariadb:10` to **`mariadb:11.4`**.
- Replaced the former MariaDB test-service environment variables for an empty root password, server character set and collation with `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1`.
- Replaced the MariaDB `mysqladmin ping` health check with `healthcheck.sh --connect --innodb_initialized` and increased the health-check retry count from three to five.

### Release metadata

- Updated `version.php` from numeric plugin version `2026101200` / release `1.1.4` to `2026101500` / release `1.1.5`.
- The required Moodle build remains `2024100700`; the component and stable maturity are unchanged.

### Scope

This published-tag comparison contains four commits and changes **only** `.github/workflows/ci.yml` and `version.php`. No activity runtime functionality, templates, classes or tests changed. The CI database image changes do not, by themselves, establish new production database version requirements.

---

These release notes are based exclusively on published Git tag diffs. They do not include changes from later `master` commits and do not imply tests or deployments were performed while creating the changelog.
