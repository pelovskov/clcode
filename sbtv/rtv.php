<?php
/* =========================================================================
   Søgeindeks · Online — Roskilde TV's arbejdsliste med fælles prikker
   Viser rtv-skabelon.html (lavet af tools/lav-rtv-arbejdsliste.py) for dem,
   der er logget ind, og gemmer prikkerne i data/rtv-status.json.
     GET  rtv.php           siden
     GET  rtv.php?status    prikkerne som JSON (og hvilke videoer der har et
                            mediekort i søgeindekset – de er altid grønne)
     POST rtv.php           sæt en prik: id, s (roed, gul, groen eller tom), csrf
   ========================================================================= */

require __DIR__ . '/faelles.php';

$erApi = isset($_GET['status']) || $_SERVER['REQUEST_METHOD'] === 'POST';

function rtv_json(array $d, int $kode = 200): void
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$bruger = aktuel_bruger();
if ($bruger === null) {
    if ($erApi) rtv_json(['ok' => false, 'fejl' => 'Du er ikke logget ind mere. Genindlæs siden og log ind igen.'], 401);
    kraev_login();
}
if (skal_skifte_kode($bruger)) {
    if ($erApi) rtv_json(['ok' => false, 'fejl' => 'Vælg først din egen adgangskode under «Skift adgangskode».'], 403);
    header('Location: kode.php');
    exit;
}

/* ---- Prikkerne som JSON ---- */
if (isset($_GET['status']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $auto = [];
    foreach (hent_indeks()['poster'] as $p) {
        if (!empty($p['video'])) $auto[$p['video']] = true;
    }
    $status = laes_json(RTV_STATUS, []);
    rtv_json(['ok' => true, 'status' => (object)$status, 'auto' => array_keys($auto)]);
}

/* ---- Sæt en prik ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) rtv_json(['ok' => false, 'fejl' => 'Siden var udløbet. Genindlæs siden, og prøv igen.'], 400);
    $id = (string)($_POST['id'] ?? '');
    $s = (string)($_POST['s'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $id) || !in_array($s, ['', 'roed', 'gul', 'groen'], true)) {
        rtv_json(['ok' => false, 'fejl' => 'Ugyldig video eller farve.'], 400);
    }
    $post = med_laas(function () use ($id, $s, $bruger) {
        $alle = laes_json(RTV_STATUS, []);
        if ($s === '') unset($alle[$id]);
        else $alle[$id] = ['s' => $s, 'af' => $bruger, 'tid' => time()];
        if (!skriv_json(RTV_STATUS, $alle)) return false;
        return $alle[$id] ?? null;
    });
    if ($post === false) rtv_json(['ok' => false, 'fejl' => 'Prikken kunne ikke gemmes. Tjek rettighederne på mappen data/.'], 500);
    rtv_json(['ok' => true, 'post' => $post]);
}

/* ---- Siden ---- */
$skabelon = __DIR__ . '/rtv-skabelon.html';
if (!is_file($skabelon)) {
    side_top('Arbejdsliste', 'Roskilde TV', $bruger, 'rtv');
    echo '<section class="panel"><p>Filen <strong>rtv-skabelon.html</strong> mangler. Læg den op i samme mappe som rtv.php.</p></section>';
    side_bund();
    exit;
}

$mk = (string)($MEDIEKORT_ADRESSE ?? '');
if (!preg_match('#^https?://#i', $mk)) $mk = 'https://pelovskov.github.io/clcode/mediekort-bygger.html';

$jsonFlag = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$konfig = '<script>window.RTV = ' . json_encode(['api' => 'rtv.php', 'csrf' => csrf(), 'bruger' => $bruger, 'mediekort' => $mk], $jsonFlag) . ';</script>';
$topbar = '<div style="max-width:1300px;margin:0 auto;padding:10px 20px 0;font-size:.9em">'
        . '<a href="upload.php">← Læg filer op</a> · Logget ind som <strong>' . h($bruger) . '</strong> · <a href="logout.php">Log ud</a></div>';

$html = (string)file_get_contents($skabelon);
$i = strpos($html, '<script>');
if ($i !== false) $html = substr($html, 0, $i) . $konfig . "\n" . substr($html, $i);
$html = preg_replace('/<body>/', "<body>\n" . $topbar, $html, 1);

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');
echo $html;
