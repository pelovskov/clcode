<?php
/* =========================================================================
   SFB-afspiller · adminsiden · trin 1 (nov. 2026)
   Her kan to personer logge ind og passe historiehylden uden FTP:
   se hylden og ventepositionen, flytte zip-filer frem og tilbage og slette
   dem fra ventepositionen. Upload og datoer kommer i de næste trin.

   Siden er selvstændig: Den bruger kun filerne i afspillerens egen mappe og
   intet fra Søgeindeks · Online. Login følger samme opskrift som der:
   bcrypt-hash i config.php, bremse på forkerte forsøg, beskyttelse af alle
   knapper (CSRF) og en login-cookie, der kun virker over https.

   config.php og mappen data/ kommer aldrig i repoet (se .gitignore).
   Se afspiller/LAES-MIG.md under «Adminsiden».
   ========================================================================= */

declare(strict_types=1);

const MAKS_FORSOEG = 5;          // forkerte forsøg, før man skal vente
const PAUSE_SEK    = 15 * 60;    // så længe man skal vente
const UDLOEB_SEK   = 2 * 3600;   // man logges ud efter 2 timer uden brug
const NAVN_MOENSTER = '/^[a-z0-9][a-z0-9._-]*\.zip$/';

define('AFSPILLER', dirname(__DIR__));
define('MAPPER', ['hylde' => AFSPILLER . '/hylde', 'venter' => AFSPILLER . '/venter']);
define('DATA', __DIR__ . '/data');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- Opsætning ---------- */
$BRUGERE = [];
$harConfig = is_file(__DIR__ . '/config.php');
if ($harConfig) require __DIR__ . '/config.php';
/* data/ og venter/ skal være spærret for besøgende. Mangler mapperne eller
   spærringen (.htaccess) på webhotellet, laves de her, så intet ligger åbent. */
const SPAERRING = "# Ingen adgang udefra til denne mappe\n<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n";
foreach ([DATA, MAPPER['venter']] as $m) {
    if (!is_dir($m)) @mkdir($m, 0755, true);
    if (is_dir($m) && !is_file($m . '/.htaccess')) @file_put_contents($m . '/.htaccess', SPAERRING);
}

/* ---------- Små hjælpere til filer ---------- */
function laes_json(string $fil, $standard) {
    if (!is_file($fil)) return $standard;
    $d = json_decode((string)file_get_contents($fil), true);
    return $d === null ? $standard : $d;
}
function skriv_json(string $fil, $data): void {
    $tmp = $fil . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    rename($tmp, $fil);
}

/* ---------- Login ---------- */
function start_session(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    session_name('sfbadm_' . substr(md5(__DIR__), 0, 10));
    $sti = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $sti,
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
function brugere(): array {
    global $BRUGERE;
    $ud = [];
    foreach (($BRUGERE ?? []) as $navn => $hash) $ud[mb_strtolower(trim((string)$navn), 'UTF-8')] = (string)$hash;
    return $ud;
}
function tjek_kode(string $navn, string $kode): bool {
    $liste = brugere();
    $navn = mb_strtolower(trim($navn), 'UTF-8');
    if (!isset($liste[$navn])) {
        // Samme tid som et rigtigt forsøg, så man ikke kan gætte brugernavne.
        password_verify($kode, '$2y$12$zYysRgL.KbVNxVsR90uv/OSHL/VbfN0xW915ioFjlsYhj4b/kNkBu');
        return false;
    }
    $hash = $liste[$navn];
    if (strncmp($hash, '$2b$', 4) === 0) $hash = '$2y$' . substr($hash, 4);
    return password_verify($kode, $hash);
}
function aktuel_bruger(): ?string {
    start_session();
    $b = $_SESSION['bruger'] ?? null;
    if ($b === null || !isset(brugere()[$b])) return null;
    if (time() - (int)($_SESSION['sidst'] ?? 0) > UDLOEB_SEK) { $_SESSION = []; return null; }
    $_SESSION['sidst'] = time();
    return $b;
}
function csrf(): string {
    start_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_felt(): string { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }
function csrf_ok(): bool {
    start_session();
    return !empty($_SESSION['csrf']) && is_string($_POST['csrf'] ?? null) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* Bremse på gættede adgangskoder, pr. IP-adresse (gemt som hash). */
function forsoeg_ip(): string { return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '?')); }
function laast_ude(): int {
    $f = laes_json(DATA . '/login-forsoeg.json', []);
    $r = $f[forsoeg_ip()] ?? null;
    if (!$r || $r['antal'] < MAKS_FORSOEG) return 0;
    return max(0, ($r['sidst'] + PAUSE_SEK) - time());
}
function registrer_forsoeg(bool $lykkedes): void {
    $fil = DATA . '/login-forsoeg.json';
    $f = laes_json($fil, []);
    foreach ($f as $k => $r) if (($r['sidst'] ?? 0) < time() - PAUSE_SEK) unset($f[$k]);
    $ip = forsoeg_ip();
    if ($lykkedes) unset($f[$ip]);
    else $f[$ip] = ['antal' => (($f[$ip]['antal'] ?? 0) + 1), 'sidst' => time()];
    skriv_json($fil, $f);
}

/* ---------- Hylden og ventepositionen ---------- */
function gyldigt_navn(string $navn): bool { return $navn === basename($navn) && (bool)preg_match(NAVN_MOENSTER, $navn); }
function filer_i(string $hvor): array {
    $ud = [];
    foreach (glob(MAPPER[$hvor] . '/*.zip') ?: [] as $sti) {
        $navn = basename($sti);
        if (!gyldigt_navn($navn) || !is_file($sti)) continue;
        $ud[] = ['navn' => $navn, 'bytes' => filesize($sti), 'tid' => filemtime($sti), 'titel' => titel($sti)];
    }
    usort($ud, function ($a, $b) { return $b['tid'] <=> $a['tid']; });
    return $ud;
}
/* Titlen læses i sedlen inde i zip-filen (builder-source-data). Den huskes i
   data/titler.json, så store zip-filer ikke skal læses, hver gang siden vises. */
function titel(string $sti): string {
    static $cache = null;
    if ($cache === null) $cache = laes_json(DATA . '/titler.json', []);
    $noegle = basename($sti) . '|' . filesize($sti) . '|' . filemtime($sti);
    if (isset($cache[$noegle])) return $cache[$noegle];
    $titel = '';
    if (class_exists('ZipArchive')) {
        $z = new ZipArchive();
        if ($z->open($sti) === true) {
            for ($i = 0; $i < $z->numFiles && $titel === ''; $i++) {
                $st = $z->statIndex($i);
                $n = $st['name'] ?? '';
                if (!preg_match('/\.html?$/i', $n) || substr_count($n, '/') > 1 || strpos($n, '__MACOSX') === 0) continue;
                if (($st['size'] ?? 0) > 60 * 1024 * 1024) continue;
                $html = $z->getFromIndex($i);
                if (!is_string($html) || !preg_match('#<script[^>]*id="builder-source-data"[^>]*>(.*?)</script>#s', $html, $m)) continue;
                $d = json_decode($m[1], true);
                if (!is_array($d) || !empty($d['overordnet'])) continue;
                $titel = (string)($d['title'] ?? $d['titel'] ?? ($d['dublinCore']['title'] ?? ''));
            }
            $z->close();
        }
    }
    $cache[$noegle] = $titel;
    if (count($cache) > 400) $cache = array_slice($cache, -400, null, true);
    skriv_json(DATA . '/titler.json', $cache);
    return $titel;
}
function fmt_bytes(int $b): string {
    if ($b >= 1024 * 1024 * 1024) return number_format($b / 1073741824, 1, ',', '.') . ' GB';
    if ($b >= 1024 * 1024) return number_format($b / 1048576, 1, ',', '.') . ' MB';
    return max(1, (int)round($b / 1024)) . ' KB';
}

/* ---------- Beskeder mellem to sidevisninger ---------- */
function besked(string $tekst, bool $fejl = false): void { start_session(); $_SESSION['besked'] = [$tekst, $fejl]; }
function tilbage(): void { header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?')); exit; }

/* ---------- Handlinger (kun POST, altid med CSRF) ---------- */
start_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $handling = (string)($_POST['handling'] ?? '');
    if (!csrf_ok()) { besked('Siden var for gammel. Prøv igen.', true); tilbage(); }

    if ($handling === 'login') {
        $vent = laast_ude();
        if ($vent > 0) { besked('For mange forkerte forsøg. Vent ' . ceil($vent / 60) . ' minutter, og prøv igen.', true); tilbage(); }
        $navn = mb_strtolower(trim((string)($_POST['navn'] ?? '')), 'UTF-8');
        if (tjek_kode($navn, (string)($_POST['kode'] ?? ''))) {
            registrer_forsoeg(true);
            session_regenerate_id(true);
            $_SESSION['bruger'] = $navn;
            $_SESSION['sidst'] = time();
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        } else {
            registrer_forsoeg(false);
            sleep(1);
            besked('Brugernavn eller adgangskode passer ikke.', true);
        }
        tilbage();
    }

    $bruger = aktuel_bruger();
    if ($bruger === null) { besked('Du er blevet logget ud. Log ind igen.', true); tilbage(); }

    if ($handling === 'logud') { $_SESSION = []; session_destroy(); tilbage(); }

    $fil = (string)($_POST['fil'] ?? '');
    $fra = (string)($_POST['fra'] ?? '');
    if (!gyldigt_navn($fil) || !isset(MAPPER[$fra]) || !is_file(MAPPER[$fra] . '/' . $fil)) {
        besked('Filen findes ikke længere. Siden er opdateret.', true); tilbage();
    }

    if ($handling === 'flyt') {
        $til = $fra === 'hylde' ? 'venter' : 'hylde';
        if (is_file(MAPPER[$til] . '/' . $fil)) {
            besked('Der ligger allerede en fil med navnet ' . $fil . ' ' . ($til === 'hylde' ? 'på hylden' : 'i ventepositionen') . '. Flyt eller slet den først.', true);
            tilbage();
        }
        if (!@rename(MAPPER[$fra] . '/' . $fil, MAPPER[$til] . '/' . $fil)) { besked('Filen kunne ikke flyttes. Tjek, at mapperne må skrives i.', true); tilbage(); }
        // På hylden får den et nyt tidspunkt: Den står øverst og får mærket «Nyt» i appen igen.
        if ($til === 'hylde') @touch(MAPPER[$til] . '/' . $fil);
        besked($til === 'hylde' ? $fil . ' ligger nu på hylden.' : $fil . ' er flyttet til ventepositionen. Den vises ikke længere på hylden.');
        tilbage();
    }

    if ($handling === 'slet') {
        if ($fra !== 'venter' || ($_POST['bekraeft'] ?? '') !== 'ja') { besked('Kun filer i ventepositionen kan slettes. Flyt filen dertil først.', true); tilbage(); }
        if (!@unlink(MAPPER['venter'] . '/' . $fil)) { besked('Filen kunne ikke slettes.', true); tilbage(); }
        besked($fil . ' er slettet.');
        tilbage();
    }
    tilbage();
}

/* ---------- Siden ---------- */
$bruger = $harConfig ? aktuel_bruger() : null;
$besked = $_SESSION['besked'] ?? null;
unset($_SESSION['besked']);

function liste(string $hvor, array $filer): string {
    if (!$filer) return '<p class="tom">' . ($hvor === 'hylde' ? 'Der er ingen historier på hylden.' : 'Der ligger ingen historier i ventepositionen.') . '</p>';
    $ud = '<ul class="filer">';
    foreach ($filer as $f) {
        $ud .= '<li><div class="navn"><strong>' . h($f['titel'] !== '' ? $f['titel'] : $f['navn']) . '</strong>'
            . '<span>' . h($f['navn']) . ' · ' . h(fmt_bytes($f['bytes'])) . ' · ' . h(date('j/n Y \k\l. H.i', $f['tid'])) . '</span></div>'
            . '<div class="knapper">'
            . '<form method="post">' . csrf_felt() . '<input type="hidden" name="handling" value="flyt"><input type="hidden" name="fra" value="' . h($hvor) . '"><input type="hidden" name="fil" value="' . h($f['navn']) . '">'
            . '<button type="submit">' . ($hvor === 'hylde' ? 'Flyt til venteposition' : 'Læg på hylden') . '</button></form>';
        if ($hvor === 'venter') {
            $ud .= '<form method="post" class="slet" data-navn="' . h($f['titel'] !== '' ? $f['titel'] : $f['navn']) . '">' . csrf_felt()
                . '<input type="hidden" name="handling" value="slet"><input type="hidden" name="fra" value="venter"><input type="hidden" name="fil" value="' . h($f['navn']) . '">'
                . '<input type="hidden" name="bekraeft" value="">'
                . '<button type="submit" class="farlig">Slet</button></form>';
        }
        $ud .= '</div></li>';
    }
    return $ud . '</ul>';
}
?><!DOCTYPE html>
<html lang="da">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Historiehylden · admin</title>
<style>
  :root{ --accent:#8E1F49; --accent-mork:#6E1638; --accent-lys:#F1E7EC; --papir:#FAF9F5; --ink:#241520; --body:#33202B;
    --muted:#58424D; --line:#D9C7CF; --ok:#2F5D34; --fejl:#9b1c1c;
    --sans:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; --serif:Georgia, "Times New Roman", serif; }
  *{box-sizing:border-box}
  body{margin:0; background:var(--papir); color:var(--body); font-family:var(--sans); font-size:17px; line-height:1.55}
  :focus-visible{outline:3px solid var(--accent); outline-offset:2px}
  header{background:var(--accent); color:#fff}
  header .indre{max-width:900px; margin:0 auto; padding:14px 18px; display:flex; align-items:center; gap:14px; flex-wrap:wrap}
  header h1{margin:0; font-family:var(--serif); font-size:1.5rem; flex:1 1 auto}
  header a{color:#fff; font-weight:700}
  header form{margin:0}
  header button{background:transparent; color:#fff; border-color:#fff}
  main{max-width:900px; margin:0 auto; padding:22px 18px 60px}
  h2{font-family:var(--serif); color:var(--ink); font-size:1.35rem; margin:30px 0 6px}
  .hjaelp{color:var(--muted); margin:0 0 12px; font-size:.95rem}
  .besked{padding:12px 16px; border-radius:8px; background:#e7efe5; color:var(--ok); font-weight:600; margin:0 0 16px}
  .besked.fejl{background:#f6e3e3; color:var(--fejl)}
  button{font:inherit; font-weight:700; min-height:46px; padding:8px 18px; border-radius:999px; cursor:pointer; border:2px solid var(--accent); background:#fff; color:var(--accent)}
  button:hover{background:var(--accent); color:#fff}
  button.hoved{background:var(--accent); color:#fff}
  button.farlig{border-color:var(--fejl); color:var(--fejl)}
  button.farlig:hover{background:var(--fejl); color:#fff}
  ul.filer{list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:10px}
  ul.filer li{background:#fff; border:1px solid var(--line); border-radius:12px; padding:12px 14px; display:flex; flex-wrap:wrap; align-items:center; gap:10px 16px}
  ul.filer .navn{flex:1 1 18em; display:flex; flex-direction:column}
  ul.filer .navn strong{color:var(--ink); font-size:1.05rem}
  ul.filer .navn span{color:var(--muted); font-size:.9rem; word-break:break-all}
  ul.filer .knapper{display:flex; flex-wrap:wrap; gap:8px}
  ul.filer form{margin:0}
  .tom{color:var(--muted); font-style:italic}
  .login{max-width:420px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:20px}
  .login label{display:block; font-weight:700; margin:12px 0 4px; color:var(--ink)}
  .login input{width:100%; font:inherit; padding:10px 12px; border:1px solid var(--line); border-radius:8px}
  .login button{margin-top:16px}
</style>
</head>
<body>
<header><div class="indre"><h1>Historiehylden · admin</h1>
<?php if ($bruger !== null): ?>
  <span>Logget ind som <strong><?= h($bruger) ?></strong></span>
  <a href="../">Åbn afspilleren</a>
  <form method="post"><?= csrf_felt() ?><input type="hidden" name="handling" value="logud"><button type="submit">Log ud</button></form>
<?php endif; ?>
</div></header>
<main>
<?php if ($besked): ?><p class="besked<?= $besked[1] ? ' fejl' : '' ?>" role="status"><?= h($besked[0]) ?></p><?php endif; ?>

<?php if (!$harConfig || !brugere()): ?>
  <h2>Adminsiden er ikke sat op endnu</h2>
  <p>Kopiér <code>config-eksempel.php</code> til <code>config.php</code> i mappen <code>admin/</code> på webhotellet, og skriv de to brugere ind med en bcrypt-hash af deres adgangskode. Hashen laves med <code>tools/password-hash.html</code> på din egen computer.</p>

<?php elseif ($bruger === null): ?>
  <h2>Log ind</h2>
  <form method="post" class="login"><?= csrf_felt() ?><input type="hidden" name="handling" value="login">
    <label for="navn">Brugernavn</label>
    <input id="navn" name="navn" autocomplete="username" autocapitalize="none" required>
    <label for="kode">Adgangskode</label>
    <input id="kode" name="kode" type="password" autocomplete="current-password" required>
    <button type="submit" class="hoved">Log ind</button>
  </form>

<?php else: ?>
  <h2>På hylden</h2>
  <p class="hjaelp">Det, der vises i afspilleren lige nu. Flyttes en historie til ventepositionen, forsvinder den fra hylden. Har nogen hentet den til appen, beholder de den og får at vide, at den ikke længere er på hylden.</p>
  <?= liste('hylde', filer_i('hylde')) ?>

  <h2>I ventepositionen</h2>
  <p class="hjaelp">Historier, der venter på at blive brugt igen, fx julehistorier. De kan ikke ses af besøgende. Kun herfra kan en historie slettes.</p>
  <?= liste('venter', filer_i('venter')) ?>
<?php endif; ?>
</main>
<script>
  // «Er du sikker?», før en historie slettes.
  document.querySelectorAll('form.slet').forEach(function(f){
    f.addEventListener('submit', function(e){
      if (!confirm('Vil du slette «' + f.getAttribute('data-navn') + '» for altid? Det kan ikke fortrydes.')){ e.preventDefault(); return; }
      f.querySelector('input[name=bekraeft]').value = 'ja';
    });
  });
</script>
</body>
</html>
