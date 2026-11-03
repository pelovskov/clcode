<?php
/* =========================================================================
   SFB-afspiller · adminsiden · trin 2 (nov. 2026)
   Her kan to personer logge ind og passe historiehylden uden FTP:
   lægge zip-filer op, se hylden og ventepositionen, flytte zip-filer frem og
   tilbage og slette dem fra ventepositionen.
   Upload sendes i stykker på 1 MB, så webhotellets grænse for upload ikke gør
   noget. Til sidst tjekkes størrelsen, og at zip-filen indeholder en historie
   fra byggerne (en HTML-fil med sedlen builder-source-data).

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
const STYKKE       = 1024 * 1024;              // upload sendes i stykker på 1 MB
const MAKS_BYTES   = 4 * 1024 * 1024 * 1024 - 1; // zip-filer over 4 GB kan afspilleren ikke læse
const HALV_LEVETID = 24 * 3600;                // halve uploads slettes efter et døgn

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
/* Læser sedlen (builder-source-data) i zip-filen, ligesom afspilleren gør:
   en HTML-fil øverst eller i én mappe. I et galleriarkiv er det hovedalbummet.
   Giver ['titel' => …] eller null, hvis der ingen historie fra byggerne er.
   Kan webhotellet ikke læse zip-filer (ZipArchive mangler), gives ['titel' => '', 'ukendt' => true]. */
function laes_seddel(string $sti): ?array {
    if (!class_exists('ZipArchive')) return ['titel' => '', 'ukendt' => true];
    $z = new ZipArchive();
    if ($z->open($sti) !== true) return null;
    $fundet = null;
    for ($i = 0; $i < $z->numFiles && $fundet === null; $i++) {
        $st = $z->statIndex($i);
        $n = $st['name'] ?? '';
        if (!preg_match('/\.html?$/i', $n) || substr_count($n, '/') > 1 || strpos($n, '__MACOSX') === 0) continue;
        if (($st['size'] ?? 0) > 60 * 1024 * 1024) continue;
        $html = $z->getFromIndex($i);
        if (!is_string($html) || !preg_match('#<script[^>]*id="builder-source-data"[^>]*>(.*?)</script>#s', $html, $m)) continue;
        $d = json_decode($m[1], true);
        if (!is_array($d) || !empty($d['overordnet'])) continue;
        $fundet = ['titel' => (string)($d['title'] ?? $d['titel'] ?? ($d['dublinCore']['title'] ?? ''))];
    }
    $z->close();
    return $fundet;
}
function titel(string $sti): string {
    static $cache = null;
    if ($cache === null) $cache = laes_json(DATA . '/titler.json', []);
    $noegle = basename($sti) . '|' . filesize($sti) . '|' . filemtime($sti);
    if (isset($cache[$noegle])) return $cache[$noegle];
    $seddel = laes_seddel($sti);
    $titel = $seddel ? $seddel['titel'] : '';
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

/* ---------- Upload ----------
   1. «start»: navnet rettes efter reglerne, og der laves en tom fil i data/upload/.
   2. «stykke»: hvert stykke på 1 MB lægges i forlængelse af filen. Står filen et
      andet sted end ventet (fx efter et nyt forsøg), svarer webhotellet med, hvor
      langt den er, og browseren fortsætter derfra.
   3. «slut»: størrelsen og sedlen tjekkes, og filen flyttes til hylden eller
      ventepositionen. Ellers slettes den.
   Halve uploads, der er ældre end et døgn, slettes af sig selv. */
function slug_navn(string $navn): string {
    $n = mb_strtolower(preg_replace('/\.zip$/i', '', trim($navn)), 'UTF-8');
    $n = strtr($n, ['æ' => 'ae', 'ø' => 'oe', 'å' => 'aa']);
    if (function_exists('iconv')) { $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $n); if (is_string($t)) $n = $t; }
    $n = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($n)), '-');
    return ($n !== '' ? substr($n, 0, 80) : 'historie') . '.zip';
}
function upload_mappe(): string {
    $m = DATA . '/upload';
    if (!is_dir($m)) @mkdir($m, 0755, true);
    foreach (glob($m . '/*') ?: [] as $f) if (is_file($f) && filemtime($f) < time() - HALV_LEVETID) @unlink($f);
    return $m;
}
function upload_id_ok(string $id): bool { return (bool)preg_match('/^[a-f0-9]{24}$/', $id); }
function svar(array $data, int $kode = 200): void {
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- Beskeder mellem to sidevisninger ---------- */
function besked(string $tekst, bool $fejl = false): void { start_session(); $_SESSION['besked'] = [$tekst, $fejl]; }
function tilbage(): void { header('Location: ' . strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?')); exit; }

/* ---------- Handlinger (kun POST, altid med CSRF) ---------- */
start_session();

/* Upload taler med siden i små JSON-beskeder (index.php?upload=…). */
if (isset($_GET['upload'])) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') svar(['fejl' => 'Forkert forespørgsel.'], 405);
    if (aktuel_bruger() === null) svar(['fejl' => 'Du er blevet logget ud. Log ind igen.'], 401);
    $token = (string)($_SERVER['HTTP_X_CSRF'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) svar(['fejl' => 'Siden var for gammel. Genindlæs siden, og prøv igen.'], 403);
    session_write_close();   // så flere stykker ikke venter på hinanden
    $trin = (string)$_GET['upload'];
    $mappe = upload_mappe();

    if ($trin === 'start') {
        $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $bytes = (int)($in['bytes'] ?? 0);
        $til = (string)($in['til'] ?? '');
        if (!isset(MAPPER[$til])) svar(['fejl' => 'Vælg, om historien skal på hylden eller i ventepositionen.'], 400);
        if (!preg_match('/\.zip$/i', (string)($in['navn'] ?? ''))) svar(['fejl' => 'Vælg en zip-fil.'], 400);
        if ($bytes < 22) svar(['fejl' => 'Filen er tom.'], 400);
        if ($bytes > MAKS_BYTES) svar(['fejl' => 'Filen er over 4 GB, og så kan afspilleren ikke læse den.'], 400);
        $ledig = @disk_free_space($mappe);
        if ($ledig !== false && $ledig < $bytes * 2 + 50 * 1024 * 1024) svar(['fejl' => 'Der er ikke plads nok på webhotellet til filen.'], 507);
        $navn = slug_navn((string)$in['navn']);
        $findes = null;
        foreach (MAPPER as $hvor => $m) if (is_file($m . '/' . $navn)) $findes = $hvor;
        $id = bin2hex(random_bytes(12));
        file_put_contents($mappe . '/' . $id . '.part', '');
        file_put_contents($mappe . '/' . $id . '.json', json_encode(['navn' => $navn, 'bytes' => $bytes, 'til' => $til]));
        svar(['id' => $id, 'navn' => $navn, 'findes' => $findes, 'stykke' => STYKKE]);
    }

    $id = (string)($_GET['id'] ?? '');
    if (!upload_id_ok($id) || !is_file($mappe . '/' . $id . '.json')) svar(['fejl' => 'Uploaden findes ikke længere. Prøv igen.'], 404);
    $meta = json_decode((string)file_get_contents($mappe . '/' . $id . '.json'), true);
    $del = $mappe . '/' . $id . '.part';

    if ($trin === 'stykke') {
        clearstatcache();
        $har = filesize($del);
        $fra = (int)($_GET['fra'] ?? -1);
        if ($fra !== $har) svar(['modtaget' => $har]);                      // fortsæt herfra
        $ud = fopen($del, 'ab');
        $ind = fopen('php://input', 'rb');
        $skrevet = stream_copy_to_stream($ind, $ud, STYKKE + 1);
        fclose($ind); fclose($ud);
        clearstatcache();
        if ($skrevet === false || $skrevet > STYKKE || filesize($del) > $meta['bytes']) {
            @unlink($del); @unlink($mappe . '/' . $id . '.json');
            svar(['fejl' => 'Webhotellet modtog mere, end filen fylder. Prøv igen.'], 400);
        }
        svar(['modtaget' => filesize($del)]);
    }

    if ($trin === 'afbryd') {
        @unlink($del); @unlink($mappe . '/' . $id . '.json');
        svar(['ok' => true]);
    }

    if ($trin === 'slut') {
        $in = json_decode((string)file_get_contents('php://input'), true) ?: [];
        $slet = function () use ($del, $mappe, $id) { @unlink($del); @unlink($mappe . '/' . $id . '.json'); };
        clearstatcache();
        if (filesize($del) !== (int)$meta['bytes']) { $slet(); svar(['fejl' => 'Filen kom ikke helt igennem. Prøv igen.'], 400); }
        $seddel = laes_seddel($del);
        if ($seddel === null) { $slet(); svar(['fejl' => 'Zip-filen indeholder ingen historie fra byggerne. Den er ikke lagt op.'], 400); }
        $navn = $meta['navn'];
        $maal = MAPPER[$meta['til']] . '/' . $navn;
        $andet = MAPPER[$meta['til'] === 'hylde' ? 'venter' : 'hylde'] . '/' . $navn;
        if ((is_file($maal) || is_file($andet)) && empty($in['erstat'])) { $slet(); svar(['fejl' => 'Der ligger allerede en historie med navnet ' . $navn . '.'], 409); }
        if (is_file($andet) && !@unlink($andet)) { $slet(); svar(['fejl' => 'Den gamle udgave kunne ikke fjernes.'], 500); }
        if (!@rename($del, $maal)) { $slet(); svar(['fejl' => 'Filen kunne ikke lægges på plads. Tjek, at mapperne må skrives i.'], 500); }
        @unlink($mappe . '/' . $id . '.json');
        @touch($maal);
        start_session();
        $titel = $seddel['titel'] !== '' ? '«' . $seddel['titel'] . '»' : $navn;
        besked($titel . ' er lagt op ' . ($meta['til'] === 'hylde' ? 'på hylden.' : 'i ventepositionen.')
            . (!empty($seddel['ukendt']) ? ' Webhotellet kan ikke læse zip-filer, så det er ikke tjekket, at den indeholder en historie.' : ''));
        svar(['ok' => true]);
    }
    svar(['fejl' => 'Ukendt handling.'], 400);
}
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
  .upload{background:#fff; border:1px solid var(--line); border-radius:12px; padding:16px}
  .drop{display:block; border:3px dashed var(--line); border-radius:12px; padding:20px; text-align:center; cursor:pointer; background:var(--papir)}
  .drop.over{border-color:var(--accent); background:var(--accent-lys)}
  .drop strong{color:var(--ink)}
  .drop input{display:block; margin:12px auto 0; font-size:1rem; max-width:100%}
  fieldset{border:0; padding:0; margin:14px 0 0}
  legend{font-weight:700; color:var(--ink); padding:0; margin-bottom:4px}
  .valg{display:flex; gap:10px; align-items:center; min-height:40px; cursor:pointer}
  .valg input{width:20px; height:20px; accent-color:var(--accent)}
  .upload .knapper{display:flex; gap:10px; flex-wrap:wrap; margin-top:12px}
  .bjaelke{height:12px; border-radius:6px; background:var(--accent-lys); overflow:hidden; margin-top:14px}
  .bjaelke span{display:block; height:100%; width:0; background:var(--accent)}
  .upstatus{font-weight:600; margin:10px 0 0}
  .upstatus.fejl{color:var(--fejl)}
  .upload .hjaelp{margin-top:10px}
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
  <h2>Læg en historie op</h2>
  <div class="upload" id="upload" data-csrf="<?= h(csrf()) ?>">
    <label class="drop" id="drop">
      <strong>Vælg en zip-fil</strong> eller træk den herind
      <input type="file" id="zipfil" accept=".zip,application/zip">
    </label>
    <fieldset>
      <legend>Hvor skal den hen?</legend>
      <label class="valg"><input type="radio" name="til" value="hylde" checked> På hylden, så den kan ses med det samme</label>
      <label class="valg"><input type="radio" name="til" value="venter"> I ventepositionen</label>
    </fieldset>
    <div class="knapper"><button type="button" class="hoved" id="laegOp" disabled>Læg op</button><button type="button" id="afbryd" hidden>Afbryd</button></div>
    <div class="bjaelke" id="bjaelke" hidden><span></span></div>
    <p class="upstatus" id="upstatus" role="status" aria-live="polite"></p>
    <p class="hjaelp">Zip-filen skal være lavet med byggerne eller Galleriarkiv. Store filer sendes i stykker, så det kan tage lidt tid; lad siden være åben imens. Navnet rettes til små bogstaver, tal og bindestreger.</p>
  </div>

  <h2>På hylden</h2>
  <p class="hjaelp">Det, der vises i afspilleren lige nu. Flyttes en historie til ventepositionen, forsvinder den fra hylden. Har nogen hentet den til appen, beholder de den og får at vide, at den ikke længere er på hylden.</p>
  <?= liste('hylde', filer_i('hylde')) ?>

  <h2>I ventepositionen</h2>
  <p class="hjaelp">Historier, der venter på at blive brugt igen, fx julehistorier. De kan ikke ses af besøgende. Kun herfra kan en historie slettes.</p>
  <?= liste('venter', filer_i('venter')) ?>
<?php endif; ?>
</main>
<script>
  /* Upload i stykker på 1 MB. Hvert stykke prøves op til 4 gange, hvis forbindelsen
     svigter. Webhotellet siger, hvor langt det er nået, så intet sendes to gange. */
  (function(){
    var boks = document.getElementById('upload');
    if (!boks) return;
    var csrf = boks.getAttribute('data-csrf');
    var input = document.getElementById('zipfil'), knap = document.getElementById('laegOp'), afbryd = document.getElementById('afbryd');
    var bjaelke = document.getElementById('bjaelke'), status = document.getElementById('upstatus'), drop = document.getElementById('drop');
    var fil = null, stop = false, igang = false;
    function sig(t, fejl){ status.textContent = t; status.className = 'upstatus' + (fejl ? ' fejl' : ''); }
    function mb(b){ return (b / 1048576).toFixed(b < 10485760 ? 1 : 0).replace('.', ',') + ' MB'; }
    function vaelg(f){
      if (!f) return;
      if (!/\.zip$/i.test(f.name)){ fil = null; knap.disabled = true; sig('Vælg en zip-fil.', true); return; }
      fil = f; knap.disabled = false; sig(f.name + ' · ' + mb(f.size) + ' er valgt. Tryk «Læg op».');
    }
    input.addEventListener('change', function(){ vaelg(input.files[0]); });
    ['dragenter','dragover'].forEach(function(t){ drop.addEventListener(t, function(e){ e.preventDefault(); drop.classList.add('over'); }); });
    ['dragleave','drop'].forEach(function(t){ drop.addEventListener(t, function(e){ e.preventDefault(); drop.classList.remove('over'); }); });
    drop.addEventListener('drop', function(e){ if (e.dataTransfer && e.dataTransfer.files.length) vaelg(e.dataTransfer.files[0]); });
    function post(trin, krop, ekstra, type){
      return fetch('?upload=' + trin + (ekstra || ''), { method:'POST', credentials:'same-origin',
        headers:{ 'X-CSRF': csrf, 'Content-Type': type || 'application/json' }, body: krop })
        .then(function(r){ return r.json().catch(function(){ return { fejl:'Webhotellet svarede ikke som ventet (' + r.status + ').' }; }); });
    }
    function vent(ms){ return new Promise(function(r){ setTimeout(r, ms); }); }
    async function stykke(id, fra, str){
      for (var forsoeg = 0; forsoeg < 4; forsoeg++){
        try { return await post('stykke', fil.slice(fra, Math.min(fil.size, fra + str)), '&id=' + id + '&fra=' + fra, 'application/octet-stream'); }
        catch(e){ if (stop) throw e; await vent(1500 * (forsoeg + 1)); }
      }
      throw new Error('Forbindelsen blev afbrudt. Prøv igen, når der er god forbindelse.');
    }
    knap.addEventListener('click', async function(){
      if (!fil || igang) return;
      var til = (document.querySelector('input[name=til]:checked') || {}).value;
      igang = true; stop = false; knap.disabled = true; input.disabled = true; afbryd.hidden = false; bjaelke.hidden = false;
      bjaelke.firstElementChild.style.width = '0';
      var id = null;
      try {
        sig('Gør klar …');
        var s = await post('start', JSON.stringify({ navn: fil.name, bytes: fil.size, til: til }));
        if (s.fejl) throw new Error(s.fejl);
        id = s.id;
        var erstat = false;
        if (s.findes){
          erstat = confirm('Der ligger allerede en historie med navnet ' + s.navn + ' ' + (s.findes === 'hylde' ? 'på hylden' : 'i ventepositionen') + '. Vil du erstatte den med den nye?');
          if (!erstat){ await post('afbryd', '{}', '&id=' + id); sig('Intet er lagt op.'); return; }
        }
        var pos = 0;
        while (pos < fil.size){
          if (stop) throw new Error('afbrudt');
          var r = await stykke(id, pos, s.stykke);
          if (r.fejl) throw new Error(r.fejl);
          pos = r.modtaget;
          bjaelke.firstElementChild.style.width = Math.floor(pos / fil.size * 100) + '%';
          sig('Sender … ' + mb(pos) + ' af ' + mb(fil.size));
        }
        sig('Tjekker zip-filen …');
        var slut = await post('slut', JSON.stringify({ erstat: erstat }), '&id=' + id);
        if (slut.fejl) throw new Error(slut.fejl);
        sig('Færdig. Siden opdateres …');
        igang = false;
        location.reload();
      } catch(e){
        if (id && e.message === 'afbrudt'){ try { await post('afbryd', '{}', '&id=' + id); } catch(x){} bjaelke.hidden = true; sig('Uploaden er afbrudt. Intet er lagt op.'); }
        else sig(e.message || 'Det lykkedes ikke.', true);
      } finally {
        igang = false; knap.disabled = !fil; input.disabled = false; afbryd.hidden = true;
      }
    });
    afbryd.addEventListener('click', function(){ stop = true; sig('Afbryder …'); });
    window.addEventListener('beforeunload', function(e){ if (igang){ e.preventDefault(); e.returnValue = ''; } });
  })();

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
