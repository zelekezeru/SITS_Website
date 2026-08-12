# Moodle migration cutover — old 4.1 → learn.sits.edu.et (5.1)

Migrates **everything** from the old Moodle (`sits.edu.et/moodle`, Moodle 4.1, DB
`sitseduorg_mo556`) into the new one at **learn.sits.edu.et** (Moodle 5.1.5+, DB
`sitseduorg_moodle`), by cloning the old database and upgrading it forward.

Every step below was **rehearsed on a scratch copy on 2026-08-11** and the results verified
table by table. This is the record of what actually worked, not a plan on paper.

> The new Moodle's own database is *replaced* by this process. That is inherent to a full
> migration — course backup/restore cannot move users, roles, site settings or cross-course
> data. The new site currently holds 1 user and 0 courses, so nothing of value is displaced.

---

## What the rehearsal established

**The upgrade cannot be done in one hop.** Moodle 5.1 refuses any database below 4.2.3, and
4.5 refuses anything below 4.1.2 — the old site is 4.1.0 (`2022112800`). The chain that works:

| Leg | Code | PHP | Why this PHP |
|---|---|---|---|
| 4.1.0 → 4.1.22 | `~/moodle-41` | **8.1** + `-d extension=zip.so -d extension=intl.so` | Moodle 4.1 does not support PHP 8.2; this host's 8.1 CLI ships without zip/intl, but both modules are on disk |
| 4.1.22 → 4.5.12 | `~/moodle-45` | 8.2 | |
| 4.5.12 → 5.1.5 | `~/moodle` | 8.2 (default) | |

All three legs need `-d max_input_vars=5000`, or the environment check fails.

**Data verified preserved** (source → upgraded copy): users 277, courses 190, categories 24,
course sections 2146, enrolments 1671, role assignments 1700, contexts 4034, grades 8857,
grade items 430, quizzes 205, quiz attempts 4575, assignments 151, submissions 3205, forum
posts 817, files 46252, block instances 1334, competencies 1, tags 12.

`analytics_*`, `stats_*` and `log*` tables shrink. That is Moodle rebuilding derived data,
not loss.

---

## Blockers that must be handled DURING the cutover

### 1. 160 of 277 users authenticate with `auth = joomdle`

That plugin does not exist in Moodle 5.1. The upgrade leaves the value untouched, so those
accounts end up with an auth method Moodle cannot resolve — **58% of the user base locked
out**, discovered only when they try to log in.

Fix, after the upgrade completes (choose one):

```sql
-- Recommended: they log in through SITS via OIDC.
UPDATE mdlo9_user SET auth = 'oauth2' WHERE auth = 'joomdle' AND deleted = 0;
```

Anyone kept on `manual` needs a password reset — the joomdle password hashes are not usable.

### 2. 114 Moodle users have no SITS account

Only 163 of the 277 Moodle emails match a row in the Laravel `users` table. Under SSO the
other 114 have no identity to sign in with. The repo already has the importer:

```bash
cd ~/main-website
php artisan moodle:import-users --config=/home/sitseduorg/sits.edu.et/moodle/config.php
```

It maps Moodle roles → SITS roles and skips users that already exist.

### 3. Chat and survey data is destroyed unless the plugins are installed first

Moodle removed `mod_chat` and `mod_survey` from core in 5.0 and **drops their tables during
the upgrade** if the plugin code is absent. The rehearsal lost exactly: 1 chat activity
("chat room for ma", course 11 *Research Project*) with 1 message, and 5 surveys with 73
questions.

To keep them, install into `~/moodle-45` **before** running the 5.1 leg:

- <https://github.com/moodlehq/moodle-mod_chat>
- <https://github.com/moodlehq/moodle-mod_survey>

If that content is not wanted, skip this and accept the loss — it is the only user-authored
content the rehearsal lost.

### 4. `mod_customcert` — data survives, but needs its code back

Unlike chat/survey, the upgrade **left the customcert tables intact**: 2 certificate
activities, **12 issued certificates**, 3 templates, 3 elements. They are simply orphaned
because the plugin is not in the 5.1 tree. Install a 5.1-compatible `mod_customcert` after
the upgrade and the certificates return with their data.

Same situation, no data: `report/embedquestion` (0 rows), `enrol/autoenrol` (1 instance),
blocks `messages` / `mnet_hosts` / `section_links` (1 instance between them).

### 5. The cutover wipes the SSO configuration

The OAuth2 issuer, its endpoints, field mappings and `requireconfirmation` all live in the
Moodle database being replaced. Re-apply from
`~/backups/moodle-sso-20260811/oauth2-state-before.json` — with `requireconfirmation = 0`
and mappings `email→email`, `given_name→firstname`, `family_name→lastname`, `sub→idnumber`.
See `docs/moodle-sso-server-setup.md`.

---

## Cutover procedure

Budget 1–2 hours. Put the old site in maintenance mode first so no one writes to it midway.

```bash
# 0. Fresh dump of the old database (the rehearsal used a 2 Jul dump; take a current one)
STAMP=$(date +%Y%m%d-%H%M%S)
mkdir -p ~/backups/moodle-cutover-$STAMP
mysqldump --defaults-extra-file=/tmp/mold.cnf --single-transaction --quick \
  sitseduorg_mo556 > ~/backups/moodle-cutover-$STAMP/old-mo556.sql

# 1. Back up the CURRENT new database before replacing it
mysqldump --defaults-extra-file=/tmp/mdry.cnf --single-transaction --quick \
  sitseduorg_moodle > ~/backups/moodle-cutover-$STAMP/new-moodle-BEFORE.sql

# 2. Replace the new database with the old one
mysql --defaults-extra-file=/tmp/mdry.cnf -e \
  "DROP DATABASE sitseduorg_moodle; CREATE DATABASE sitseduorg_moodle DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql --defaults-extra-file=/tmp/mdry.cnf sitseduorg_moodle < ~/backups/moodle-cutover-$STAMP/old-mo556.sql

# 3. Point the config at the restored tables — the old prefix is mdlo9_, NOT mdl_
sed -i "s|^\$CFG->prefix.*|\$CFG->prefix    = 'mdlo9_';|" ~/moodle/config.php ~/moodle/public/config.php
```

Then run the three legs, each against `~/moodle`'s database by copying that config into the
staging trees (`~/moodle-41`, `~/moodle-45`) exactly as the rehearsal did:

```bash
cd ~/moodle-41 && /opt/alt/php81/usr/bin/php -d extension=zip.so -d extension=intl.so \
  -d max_input_vars=5000 admin/cli/upgrade.php --non-interactive
cd ~/moodle-45 && /opt/alt/php82/usr/bin/php -d max_input_vars=5000 \
  admin/cli/upgrade.php --non-interactive
# install mod_chat / mod_survey into ~/moodle-45 here if that content is being kept
cd ~/moodle && php -d max_input_vars=5000 admin/cli/upgrade.php --non-interactive
```

Post-upgrade, in order:

```bash
cd ~/moodle
php admin/cli/cfg.php --name=wwwroot --set=https://learn.sits.edu.et
php admin/cli/purge_caches.php
php admin/cli/maintenance.php --disable
```

Then the fixes above (auth, user import, plugins, SSO re-apply), and finally check the task
queue — the restored database carries the old site's failed/adhoc tasks (3240 failing,
oldest adhoc 124 days) which need clearing so cron is not permanently backed up.

## Verification before declaring done

```bash
php admin/cli/check_database_schema.php     # expect only contributed-plugin "not expected" tables
php admin/cli/checks.php                    # cron/task warnings are expected until cron runs
```

Then confirm by hand: log in as an SSO user, open a course, view the gradebook, open a quiz
attempt, download a submitted assignment file, and confirm an issued certificate renders.

## Rollback

The old site is never modified — it stays on `sitseduorg_mo556` and its own dataroot
throughout. If the cutover fails, restore `new-moodle-BEFORE.sql` and point DNS/docroot back.
Keep the old database untouched until users have confirmed the new site for at least a week.

## Scratch artefacts from the rehearsal

Safe to delete once the real cutover is done: database `sitseduorg_mdry`, directories
`~/moodle-dryrun`, `~/moodle-41`, `~/moodle-45`, `~/moodledata-dryrun`, and
`/tmp/m*.log`, `/tmp/sits_*.php`, `/tmp/*.cnf`.
