<?php
/**
 * SITS Moodle migration tool  —  run ON the cPanel server (SSH / cPanel Terminal).
 *
 * Layout this assumes (all under the cPanel account home, $HOME):
 *   ~/moodle-old   → the OLD Moodle, served at https://old.sits.edu.et  (archive, kept as-is)
 *   ~/moodle       → the NEW Moodle,  served at https://learn.sits.edu.et (migration target)
 * Both installs already exist and each has a working config.php, so this tool reads every
 * DB credential / path / URL straight out of those config.php files — nothing to type.
 *
 * "Complete migration" = clone the OLD database + OLD moodledata into the NEW site, then run
 * Moodle's own upgrade so ALL data (users, courses, enrolments, grades, files, cohorts,
 * settings, plugins) is migrated forward to the new version. This is the only Moodle method
 * that preserves everything (course backup/restore does NOT carry global users/site data).
 *
 * COMMANDS
 *   php moodle-migrate.php recon                 Read-only. Detect versions/creds/paths, print the plan.
 *   php moodle-migrate.php run --confirm         Do the migration OLD → NEW (destructive to the NEW db).
 *   php moodle-migrate.php fix-old --confirm     Re-point the OLD site's URL to old.sits.edu.et.
 *
 * IMPORTANT: run it with the SAME PHP the sites use, e.g.  ea-php83 moodle-migrate.php recon
 *            (subprocesses reuse this PHP binary for Moodle's CLI scripts).
 *
 * Common options (all optional — sensible defaults are auto-detected):
 *   --old-config=PATH   --new-config=PATH       Override config.php locations.
 *   --new-url=URL        Target wwwroot for the NEW site   (default: NEW config wwwroot).
 *   --old-url=URL        Source URL to rewrite FROM        (default: OLD config wwwroot).
 *   --old-archive-url=URL  URL the OLD site will live at   (default: https://old.sits.edu.et).
 *   --skip-files         Don't copy moodledata.
 *   --skip-db            Don't clone the database.
 *   --skip-upgrade       Clone only; don't run Moodle's upgrade (do it manually / for hop upgrades).
 *   --mysql=BIN --mysqldump=BIN --rsync=BIN --php=BIN   Override tool binaries.
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
set_time_limit(0);

// ---------------------------------------------------------------------------- args
$argvv = $argv;
array_shift($argvv);
$command = 'recon';
if (isset($argvv[0]) && $argvv[0][0] !== '-') { $command = array_shift($argvv); }
$opt = [];
foreach ($argvv as $a) {
    if (preg_match('/^--([^=]+)(?:=(.*))?$/', $a, $m)) { $opt[$m[1]] = $m[2] ?? true; }
}
$CONFIRM = isset($opt['confirm']);

$HOME = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
if ($HOME === '' && isset($_SERVER['DOCUMENT_ROOT'])) { $HOME = dirname($_SERVER['DOCUMENT_ROOT']); }
if ($HOME === '') { fail("Cannot determine home directory. Pass --old-config / --new-config explicitly."); }

$BIN = [
    'mysql'     => $opt['mysql']     ?? 'mysql',
    'mysqldump' => $opt['mysqldump'] ?? 'mysqldump',
    'rsync'     => $opt['rsync']     ?? 'rsync',
    'php'       => $opt['php']        ?? PHP_BINARY,
];

// ---------------------------------------------------------------------------- helpers
function fail($msg) { fwrite(STDERR, "\n[FATAL] $msg\n"); exit(1); }
function info($msg) { echo $msg . "\n"; }
function hr()       { echo str_repeat('-', 78) . "\n"; }
function head($t)   { echo "\n" . str_repeat('=', 78) . "\n  $t\n" . str_repeat('=', 78) . "\n"; }

/** First existing path from a candidate list. */
function first_existing(array $paths) {
    foreach ($paths as $p) { if ($p && file_exists($p)) return $p; }
    return null;
}

/** Parse a Moodle config.php into structured fields (regex — no bootstrap, read-only, version-proof). */
function parse_config($path) {
    $src = file_get_contents($path);
    $g = function ($key, $default = '') use ($src) {
        if (preg_match('/\$CFG->' . preg_quote($key, '/') . '\s*=\s*[\'"]([^\'"]*)[\'"]/', $src, $m)) return $m[1];
        return $default;
    };
    $c = [
        'config'  => $path,
        'coderoot'=> rtrim(dirname($path), '/'),
        'dbtype'  => $g('dbtype', 'mariadb'),
        'dbhost'  => $g('dbhost', 'localhost'),
        'dbname'  => $g('dbname'),
        'dbuser'  => $g('dbuser'),
        'dbpass'  => $g('dbpass'),
        'prefix'  => $g('prefix', 'mdl_'),
        'dataroot'=> $g('dataroot'),
        'wwwroot' => $g('wwwroot'),
    ];
    // version.php lives in the webroot (== code root pre-5.x, or the 5.x public/ dir).
    $vfile = first_existing([$c['coderoot'] . '/version.php', dirname($c['coderoot']) . '/version.php']);
    $c['release'] = $c['version'] = null;
    if ($vfile) {
        $vsrc = file_get_contents($vfile);
        if (preg_match('/\$release\s*=\s*[\'"]([^\'"]+)[\'"]/', $vsrc, $m)) $c['release'] = $m[1];
        if (preg_match('/\$version\s*=\s*([0-9]+(?:\.[0-9]+)?)/', $vsrc, $m)) $c['version'] = $m[1];
    }
    return $c;
}

/** Locate a Moodle CLI script relative to the code root, tolerating the 5.x public/ layout. */
function cli_path($coderoot, $rel) {
    return first_existing([
        "$coderoot/$rel",              // classic (webroot == code root, and 5.x public/ webroot)
        dirname($coderoot) . "/$rel",  // fallback: config in public/ but tool at project root
    ]);
}

/** PDO connect from a parsed config. */
function db_connect($c) {
    $dsn = "mysql:host={$c['dbhost']};dbname={$c['dbname']};charset=utf8mb4";
    return new PDO($dsn, $c['dbuser'], $c['dbpass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/** Read a handful of facts from a live Moodle DB (best-effort; tolerates a connectable-but-empty DB). */
function db_probe($c) {
    $out = ['ok' => false, 'version' => null, 'release' => null, 'siteid' => null,
            'users' => null, 'courses' => null, 'tables' => null];
    try {
        $pdo = db_connect($c);
        $out['ok'] = true;                 // connected — individual queries below may still be absent
        $p = $c['prefix'];
        $one = function ($sql, $args = []) use ($pdo) {
            try { $s = $pdo->prepare($sql); $s->execute($args); return $s->fetchColumn(); }
            catch (Exception $e) { return null; }   // missing table on a fresh/empty DB → just null
        };
        $out['version'] = $one("SELECT value FROM {$p}config WHERE name = ?", ['version']);
        $out['release'] = $one("SELECT value FROM {$p}config WHERE name = ?", ['release']);
        $out['siteid']  = $one("SELECT value FROM {$p}config WHERE name = ?", ['siteidentifier']);
        $out['users']   = $one("SELECT COUNT(*) FROM {$p}user WHERE deleted = 0");
        $out['courses'] = $one("SELECT COUNT(*) FROM {$p}course");   // includes site course id=1
        $out['tables']  = $one("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()");
    } catch (Exception $e) {
        $out['error'] = $e->getMessage();
    }
    return $out;
}

/** Write a locked-down defaults-extra-file so passwords never hit the process list / shell history. */
function write_mycnf($c) {
    $tmp = sys_get_temp_dir() . '/.mmig-' . substr(md5($c['dbname'] . $c['dbuser'] . mt_rand()), 0, 10) . '.cnf';
    file_put_contents($tmp, "[client]\nhost={$c['dbhost']}\nuser={$c['dbuser']}\npassword=\"{$c['dbpass']}\"\ndefault-character-set=utf8mb4\n");
    chmod($tmp, 0600);
    return $tmp;
}

function run_shell($cmd, $label) {
    info("\n\$ $label");
    $rc = 0;
    passthru($cmd, $rc);
    if ($rc !== 0) fail("Step failed (exit $rc): $label");
    return $rc;
}

/** Like run_shell but a non-zero exit only warns (for cosmetic steps: URL rewrite, cache purge). */
function run_shell_soft($cmd, $label) {
    info("\n\$ $label");
    $rc = 0;
    passthru($cmd, $rc);
    if ($rc !== 0) info("⚠ Non-fatal (exit $rc) — continuing: $label");
    return $rc;
}

// ---------------------------------------------------------------------------- locate configs
$oldCfgPath = $opt['old-config'] ?? first_existing([
    "$HOME/moodle-old/config.php", "$HOME/moodle-old/public/config.php",
]);
$newCfgPath = $opt['new-config'] ?? first_existing([
    "$HOME/moodle/config.php", "$HOME/moodle/public/config.php",
]);
if (!$oldCfgPath) fail("OLD Moodle config.php not found under ~/moodle-old (or /public). Pass --old-config=PATH.");
if (!$newCfgPath) fail("NEW Moodle config.php not found under ~/moodle (or /public). Pass --new-config=PATH.");

$OLD = parse_config($oldCfgPath);
$NEW = parse_config($newCfgPath);
$oldProbe = db_probe($OLD);
$newProbe = db_probe($NEW);

$OLD_URL = $opt['old-url'] ?? $OLD['wwwroot'];        // URL currently baked into old content
$NEW_URL = $opt['new-url'] ?? $NEW['wwwroot'];        // target URL for the migrated site
$OLD_ARCHIVE_URL = $opt['old-archive-url'] ?? 'https://old.sits.edu.et';

// ---------------------------------------------------------------------------- report (all commands print this)
head("SITS Moodle migration — recon");
foreach ([['OLD (archive → old.sits.edu.et)', $OLD, $oldProbe], ['NEW (target → learn.sits.edu.et)', $NEW, $newProbe]] as [$title, $c, $pr]) {
    info("• $title");
    info("    config     : {$c['config']}");
    info("    code root  : {$c['coderoot']}   →  docroot MUST point here");
    info("    version.php: " . ($c['release'] ?: '??') . "   (\$version=" . ($c['version'] ?: '??') . ")");
    info("    db         : {$c['dbtype']}  {$c['dbname']}@{$c['dbhost']}  (user {$c['dbuser']}, prefix {$c['prefix']})");
    info("    dataroot   : {$c['dataroot']}");
    info("    wwwroot    : {$c['wwwroot']}");
    if ($pr['ok']) {
        info("    DB probe   : version={$pr['version']} release={$pr['release']} · users={$pr['users']} · courses(incl site)={$pr['courses']} · tables={$pr['tables']}");
    } else {
        info("    DB probe   : COULD NOT CONNECT — " . ($pr['error'] ?? 'unknown'));
    }
    hr();
}

// ---------------------------------------------------------------------------- safety checks
$sameDb   = (strtolower($OLD['dbname']) === strtolower($NEW['dbname']) && $OLD['dbhost'] === $NEW['dbhost']);
$sameData = ($OLD['dataroot'] && $OLD['dataroot'] === $NEW['dataroot']);
$sameCode = ($OLD['coderoot'] === $NEW['coderoot']);
if ($sameDb)   fail("OLD and NEW point at the SAME database ({$OLD['dbname']}). Refusing — they must be separate.");
if ($sameData) fail("OLD and NEW share the SAME dataroot ({$OLD['dataroot']}). Refusing — they must be separate.");
if ($sameCode) fail("OLD and NEW resolve to the SAME code root. Check --old-config / --new-config.");

// Version-gap guidance (Moodle refuses jumps that skip too many releases).
$oldMajor = (float) ($OLD['release'] ?? 0);
$newMajor = (float) ($NEW['release'] ?? 0);
info("Detected upgrade jump:  Moodle " . ($OLD['release'] ?: '?') . "  →  Moodle " . ($NEW['release'] ?: '?'));
if ($oldMajor && $newMajor) {
    if ($newMajor - $oldMajor > 1.0) {
        info("⚠  This is a MAJOR jump. Moodle may refuse to upgrade in one step. If `run` fails at the");
        info("   upgrade stage, follow the HOP TABLE in docs/moodle-migration.md (upgrade through");
        info("   intermediate versions), then re-run with --skip-db --skip-files to finish.");
    } else {
        info("✓  Small jump — a single direct upgrade should work.");
    }
}
hr();

// ============================================================================ RECON only
if ($command === 'recon') {
    head("Next step");
    info("Review the two blocks above. Then, to migrate ALL old data into the NEW site:");
    info("   {$BIN['php']} " . basename(__FILE__) . " run --confirm");
    info("");
    info("And to re-point the OLD site to its archive URL ({$OLD_ARCHIVE_URL}):");
    info("   {$BIN['php']} " . basename(__FILE__) . " fix-old --confirm");
    info("");
    info("Docroots to set in cPanel (Domains → the subdomain → document root):");
    info("   old.sits.edu.et   →  {$OLD['coderoot']}");
    info("   learn.sits.edu.et →  {$NEW['coderoot']}");
    exit(0);
}

// ============================================================================ FIX-OLD (archive)
if ($command === 'fix-old') {
    head("Fix OLD site → archive at {$OLD_ARCHIVE_URL}");
    if (!$CONFIRM) {
        info("DRY RUN — nothing was changed. With --confirm this will: set OLD wwwroot to {$OLD_ARCHIVE_URL},");
        info("rewrite {$OLD_URL} → {$OLD_ARCHIVE_URL} in the OLD db, and purge OLD caches. To apply:");
        info("   {$BIN['php']} " . basename(__FILE__) . " fix-old --confirm");
        exit(0);
    }
    if ($OLD_URL === $OLD_ARCHIVE_URL) info("Note: old wwwroot already equals archive URL; URL rewrite will be a no-op.");

    // 1. Patch wwwroot in OLD config.php (backup first).
    $bak = $OLD['config'] . '.bak-' . date('Ymd-His');
    if (!copy($OLD['config'], $bak)) fail("Could not back up {$OLD['config']} → $bak (check permissions).");
    $src = file_get_contents($OLD['config']);
    $src = preg_replace('/(\$CFG->wwwroot\s*=\s*)[\'"][^\'"]*[\'"]/', "$1'" . addslashes($OLD_ARCHIVE_URL) . "'", $src, 1, $n);
    file_put_contents($OLD['config'], $src);
    info("✓ config.php wwwroot updated ({$n} change). Backup: $bak");

    // 2. Rewrite URLs baked into content, then purge caches (Moodle CLI, using the OLD code + PHP).
    $replace = cli_path($OLD['coderoot'], 'admin/tool/replace/cli/replace.php');
    $purge   = cli_path($OLD['coderoot'], 'admin/cli/purge_caches.php');
    if ($replace && $OLD_URL && $OLD_URL !== $OLD_ARCHIVE_URL) {
        run_shell_soft(sprintf('%s %s --search=%s --replace=%s --non-interactive --shorten',
            escapeshellarg($BIN['php']), escapeshellarg($replace),
            escapeshellarg($OLD_URL), escapeshellarg($OLD_ARCHIVE_URL)),
            'rewrite OLD content URLs');
    }
    if ($purge) run_shell_soft(sprintf('%s %s', escapeshellarg($BIN['php']), escapeshellarg($purge)), 'purge OLD caches');

    head("OLD site archived");
    info("Now set old.sits.edu.et document root → {$OLD['coderoot']} in cPanel, then load https://old.sits.edu.et");
    exit(0);
}

// ============================================================================ RUN (migrate)
if ($command !== 'run') fail("Unknown command '$command'. Use: recon | run --confirm | fix-old --confirm");

head("Complete migration:  OLD  →  NEW   (clone db + moodledata, then upgrade)");
info("This OVERWRITES the NEW database ({$NEW['dbname']}) with an upgraded copy of the OLD data.");
info("Any content created in the fresh NEW install will be discarded (that is what 'migrate everything' means).");
if (!$CONFIRM) {
    info("\nDRY RUN — nothing was changed. Re-run with --confirm to execute:");
    info("   {$BIN['php']} " . basename(__FILE__) . " run --confirm");
    exit(0);
}
if (!$oldProbe['ok']) fail("Cannot read the OLD database — fix credentials in {$OLD['config']} first.");

$backupDir = "$HOME/moodle-migrate-backups";
@mkdir($backupDir, 0700, true);
$stamp = date('Ymd-His');
$oldCnf = write_mycnf($OLD);
$newCnf = write_mycnf($NEW);
register_shutdown_function(function () use ($oldCnf, $newCnf) { @unlink($oldCnf); @unlink($newCnf); });

// ---- Step 1: safety backup of the NEW db (so 'run' is reversible) ----
if (empty($opt['skip-db'])) {
    $newBak = "$backupDir/newdb-BEFORE-$stamp.sql";
    run_shell(sprintf('%s --defaults-extra-file=%s --single-transaction --quick --no-tablespaces --default-character-set=utf8mb4 %s > %s',
        escapeshellarg($BIN['mysqldump']), escapeshellarg($newCnf),
        escapeshellarg($NEW['dbname']), escapeshellarg($newBak)),
        "backup NEW db → $newBak");

    // ---- Step 2: dump the OLD db ----
    // (no --events: dumping events needs a privilege shared hosting often withholds; Moodle uses none)
    $oldDump = "$backupDir/olddb-$stamp.sql";
    run_shell(sprintf('%s --defaults-extra-file=%s --single-transaction --quick --routines --triggers --no-tablespaces --default-character-set=utf8mb4 %s > %s',
        escapeshellarg($BIN['mysqldump']), escapeshellarg($oldCnf),
        escapeshellarg($OLD['dbname']), escapeshellarg($oldDump)),
        "dump OLD db → $oldDump");

    // ---- Step 3: drop every table in the NEW db (leaves it empty for a clean load) ----
    info("\nDropping all tables in NEW db ({$NEW['dbname']})...");
    $pdo = db_connect($NEW);
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    info("✓ NEW db emptied.");

    // ---- Step 4: import the OLD dump into the NEW db ----
    run_shell(sprintf('%s --defaults-extra-file=%s %s < %s',
        escapeshellarg($BIN['mysql']), escapeshellarg($newCnf),
        escapeshellarg($NEW['dbname']), escapeshellarg($oldDump)),
        "load OLD data into NEW db");

    // ---- Step 5: the NEW config prefix MUST match the dumped table prefix ----
    if ($OLD['prefix'] !== $NEW['prefix']) {
        $bak = $NEW['config'] . '.bak-' . $stamp;
        if (!copy($NEW['config'], $bak)) fail("Could not back up {$NEW['config']} → $bak (check permissions).");
        $src = file_get_contents($NEW['config']);
        $src = preg_replace('/(\$CFG->prefix\s*=\s*)[\'"][^\'"]*[\'"]/', "$1'" . addslashes($OLD['prefix']) . "'", $src, 1);
        file_put_contents($NEW['config'], $src);
        info("✓ NEW config.php prefix set to '{$OLD['prefix']}' to match imported tables. Backup: $bak");
    }
} else {
    info("(--skip-db: leaving NEW database untouched)");
}

// ---- Step 6: copy moodledata (content files) OLD → NEW, excluding regenerable caches ----
if (empty($opt['skip-files'])) {
    if (!$OLD['dataroot'] || !$NEW['dataroot']) fail("dataroot missing in a config; cannot copy moodledata.");
    $ex = ['cache', 'localcache', 'sessions', 'temp', 'trashdir', 'muc', 'lock'];
    $exArgs = implode(' ', array_map(fn ($d) => '--exclude=' . escapeshellarg($d . '/'), $ex));
    run_shell(sprintf('%s -a %s %s %s',
        escapeshellarg($BIN['rsync']), $exArgs,
        escapeshellarg(rtrim($OLD['dataroot'], '/') . '/'),
        escapeshellarg(rtrim($NEW['dataroot'], '/') . '/')),
        "copy moodledata OLD → NEW (rsync, minus caches)");
} else {
    info("(--skip-files: leaving NEW moodledata untouched)");
}

// ---- Step 7: run Moodle's upgrade (schema OLD → NEW code version) ----
if (empty($opt['skip-upgrade'])) {
    $upgrade = cli_path($NEW['coderoot'], 'admin/cli/upgrade.php');
    if (!$upgrade) fail("upgrade.php not found under {$NEW['coderoot']}/admin/cli — check the NEW code path.");
    info("\nRunning Moodle upgrade (this is where a too-large version jump would be rejected)...");
    $rc = 0;
    passthru(sprintf('%s %s --non-interactive --allow-unstable', escapeshellarg($BIN['php']), escapeshellarg($upgrade)), $rc);
    if ($rc !== 0) {
        head("UPGRADE STOPPED (exit $rc)");
        info("The data is already cloned into the NEW db/moodledata. If Moodle refused the jump,");
        info("do a HOP upgrade (see docs/moodle-migration.md): temporarily check out an intermediate");
        info("Moodle version into the NEW code dir, run its admin/cli/upgrade.php, repeat up to the");
        info("target version, then finish with:  {$BIN['php']} " . basename(__FILE__) . " run --confirm --skip-db --skip-files");
        exit($rc);
    }
} else {
    info("(--skip-upgrade: not upgrading — do it manually)");
}

// ---- Step 8: rewrite content URLs OLD → NEW, then purge caches ----
if ($OLD_URL && $NEW_URL && $OLD_URL !== $NEW_URL) {
    $replace = cli_path($NEW['coderoot'], 'admin/tool/replace/cli/replace.php');
    if ($replace) {
        run_shell_soft(sprintf('%s %s --search=%s --replace=%s --non-interactive --shorten',
            escapeshellarg($BIN['php']), escapeshellarg($replace),
            escapeshellarg($OLD_URL), escapeshellarg($NEW_URL)),
            "rewrite content URLs {$OLD_URL} → {$NEW_URL}");
    } else {
        info("⚠ replace.php not found — after verifying, rewrite {$OLD_URL} → {$NEW_URL} manually.");
    }
}
$purge = cli_path($NEW['coderoot'], 'admin/cli/purge_caches.php');
if ($purge) run_shell_soft(sprintf('%s %s', escapeshellarg($BIN['php']), escapeshellarg($purge)), 'purge NEW caches');

head("MIGRATION COMPLETE");
info("All OLD data is now on the NEW site ({$NEW_URL}), upgraded to " . ($NEW['release'] ?: 'the new version') . ".");
info("Backups kept in: $backupDir");
info("");
info("Finish up:");
info("  1. cPanel docroots:  learn.sits.edu.et → {$NEW['coderoot']}   |   old.sits.edu.et → {$OLD['coderoot']}");
info("  2. Archive the OLD site:  {$BIN['php']} " . basename(__FILE__) . " fix-old --confirm");
info("  3. Configure OAuth2 SSO in the NEW Moodle (docs/moodle-migration.md, Phase E).");
info("  4. Verify: log into https://learn.sits.edu.et as an existing user; check courses/grades/files.");
