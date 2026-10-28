<?php
/* =========================================================================
   Søgeindeks · Online — admin
   Slet filer, genopbyg søgesiden, ret overskrift, styr redaktører og se hvem
   der har gjort hvad. Kun for superadmins.
   Slettede filer lægges i data/papirkurv/, så de kan hentes tilbage med FTP.
   ========================================================================= */

require __DIR__ . '/faelles.php';
$bruger = kraev_login(true);

$besked = '';
$fejl = '';
$engangskode = '';      // vises kun én gang, lige efter den er lavet
$engangsTil = '';

function papirkurv_indhold(): array
{
    $ud = ['antal' => 0, 'bytes' => 0];
    foreach (scandir(PAPIRKURV) ?: [] as $f) {
        if ($f[0] === '.' || !is_file(PAPIRKURV . '/' . $f)) continue;
        $ud['antal']++;
        $ud['bytes'] += (int)filesize(PAPIRKURV . '/' . $f);
    }
    return $ud;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $fejl = 'Siden var udløbet. Prøv igen.';
    } else {
        $handling = (string)($_POST['handling'] ?? '');

        if ($handling === 'slet') {
            $navn = rens_filnavn((string)($_POST['fil'] ?? ''));
            if ($navn === null || er_reserveret($navn) || !is_file(FIL_MAPPE . '/' . $navn)) {
                $fejl = 'Filen findes ikke. Måske er den allerede slettet.';
            } else {
                $titel = hent_indeks()['poster'][$navn]['title'] ?? $navn;
                $ok = med_laas(function () use ($navn) {
                    $til = PAPIRKURV . '/' . date('Y-m-d_His') . '_' . $navn;
                    if (!rename(FIL_MAPPE . '/' . $navn, $til)) return false;
                    indeks_fjern($navn);
                    return true;
                });
                if ($ok) {
                    log_haendelse($bruger, 'slettede: ' . $navn);
                    $besked = '"' . $titel . '" er slettet og fjernet fra søgesiden.';
                } else {
                    $fejl = 'Filen kunne ikke slettes. Tjek rettighederne på mappen filer/.';
                }
            }
        }

        elseif ($handling === 'genopbyg') {
            @set_time_limit(300);
            $idx = med_laas(fn() => indeks_genopbyg());
            log_haendelse($bruger, 'genopbyggede søgesiden');
            $medBillede = count(array_filter($idx['poster'], fn($p) => !empty($p['thumb'])));
            $besked = 'Søgesiden er bygget forfra. ' . count($idx['poster']) . ' filer er med'
                    . ($idx['sprunget'] ? ', ' . count($idx['sprunget']) . ' er sprunget over.' : '.')
                    . ' ' . $medBillede . ' af dem har et lille billede.';
        }

        elseif ($handling === 'titel') {
            $t = trim(mb_substr((string)($_POST['titel'] ?? ''), 0, 150));
            $u = trim(mb_substr((string)($_POST['undertitel'] ?? ''), 0, 200));
            med_laas(function () use ($t, $u) {
                skriv_json(INDSTILLINGER, ['titel' => $t !== '' ? $t : 'Søg i lokalhistorier', 'undertitel' => $u]);
                skriv_statisk_soegeside(hent_indeks());
            });
            log_haendelse($bruger, 'ændrede overskriften');
            $besked = 'Overskriften er gemt.';
        }

        elseif ($handling === 'ny_bruger') {
            $navn = mb_strtolower(trim((string)($_POST['navn'] ?? '')), 'UTF-8');
            if (!gyldigt_brugernavn($navn)) {
                $fejl = 'Brugernavnet skal være 2–30 tegn: små bogstaver, tal, punktum, bindestreg eller understreg.';
            } elseif (isset(config_brugere()[$navn]) || isset(redaktoerer()[$navn])) {
                $fejl = 'Der findes allerede en bruger med navnet "' . $navn . '".';
            } else {
                $kode = lav_engangskode();
                $ok = med_laas(function () use ($navn, $kode, $bruger) {
                    $r = redaktoerer();
                    if (isset($r[$navn])) return false;
                    $r[$navn] = ['hash' => lav_hash($kode), 'aktiv' => true, 'skal_skifte' => true,
                                 'oprettet' => time(), 'oprettet_af' => $bruger];
                    ksort($r);
                    return skriv_json(BRUGER_FIL, $r);
                });
                if ($ok) {
                    log_haendelse($bruger, 'oprettede redaktøren ' . $navn);
                    $engangskode = $kode; $engangsTil = $navn;
                } else {
                    $fejl = 'Brugeren kunne ikke oprettes. Tjek rettighederne på mappen data/.';
                }
            }
        }

        elseif (in_array($handling, ['ny_kode', 'luk', 'genaabn'], true)) {
            $navn = (string)($_POST['navn'] ?? '');
            if (!er_redaktoer_fra_admin($navn)) {
                $fejl = 'Brugeren findes ikke, eller den står i config.php og kan kun rettes der.';
            } else {
                $kode = $handling === 'ny_kode' ? lav_engangskode() : '';
                $ok = med_laas(function () use ($navn, $handling, $kode) {
                    $r = redaktoerer();
                    if (!isset($r[$navn])) return false;
                    if ($handling === 'ny_kode'){ $r[$navn]['hash'] = lav_hash($kode); $r[$navn]['skal_skifte'] = true; }
                    if ($handling === 'luk'){ $r[$navn]['aktiv'] = false; $r[$navn]['lukket'] = time(); }
                    if ($handling === 'genaabn'){ $r[$navn]['aktiv'] = true; unset($r[$navn]['lukket']); }
                    return skriv_json(BRUGER_FIL, $r);
                });
                if (!$ok) {
                    $fejl = 'Ændringen kunne ikke gemmes. Tjek rettighederne på mappen data/.';
                } elseif ($handling === 'ny_kode') {
                    log_haendelse($bruger, 'lavede ny engangskode til ' . $navn);
                    $engangskode = $kode; $engangsTil = $navn;
                } elseif ($handling === 'luk') {
                    log_haendelse($bruger, 'lukkede redaktøren ' . $navn);
                    $besked = $navn . ' er lukket og kan ikke logge ind længere.';
                } else {
                    log_haendelse($bruger, 'genåbnede redaktøren ' . $navn);
                    $besked = $navn . ' er åbnet igen og kan logge ind med sin gamle adgangskode.';
                }
            }
        }

        elseif ($handling === 'toem') {
            $n = 0;
            foreach (scandir(PAPIRKURV) ?: [] as $f) {
                if ($f[0] !== '.' && is_file(PAPIRKURV . '/' . $f) && @unlink(PAPIRKURV . '/' . $f)) $n++;
            }
            log_haendelse($bruger, 'tømte papirkurven (' . $n . ' filer)');
            $besked = 'Papirkurven er tømt.';
        }
    }
}

$indeks = hent_indeks();
$log = laes_json(UPLOAD_LOG, []);
$ind = indstillinger();
$kurv = papirkurv_indhold();

/* Tjek om der ligger filer i mappen, som indekset ikke kender (fx lagt op med FTP). */
$ukendte = 0;
foreach (scandir(FIL_MAPPE) ?: [] as $f) {
    if ($f[0] === '.' || !preg_match('/\.html?$/i', $f) || er_reserveret($f)) continue;
    if (!isset($indeks['poster'][$f]) && !isset($indeks['sprunget'][$f])) $ukendte++;
}
$forsvundne = 0;
foreach (array_keys($indeks['poster']) as $f) {
    if (!is_file(FIL_MAPPE . '/' . $f)) $forsvundne++;
}

$poster = $indeks['poster'];
uasort($poster, fn($a, $b) => strcoll(mb_strtolower($a['title']), mb_strtolower($b['title'])));

side_top('Admin', 'Ryd op, byg søgesiden forfra og ret overskriften.', $bruger, 'admin');
?>

<?php if ($besked !== ''): ?>
<div class="besked" role="status"><p><?= h($besked) ?></p></div>
<?php endif; ?>
<?php if ($fejl !== ''): ?>
<div class="besked fejl-b" role="alert"><p><?= h($fejl) ?></p></div>
<?php endif; ?>
<?php if ($engangskode !== ''): ?>
<div class="besked" role="status" id="engangskode" tabindex="-1">
  <p>Engangskode til <strong><?= h($engangsTil) ?></strong>:</p>
  <p style="font-size:1.5rem;font-family:ui-monospace,Menlo,Consolas,monospace;letter-spacing:.05em;margin:.4rem 0"><?= h($engangskode) ?></p>
  <p>Giv koden til <?= h($engangsTil) ?> personligt eller i en sms. Den vises kun nu.
    Første gang <?= h($engangsTil) ?> logger ind, skal vedkommende vælge sin egen adgangskode.</p>
<?php $loginAdr = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://'
        . ($_SERVER['HTTP_HOST'] ?? '') . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/login.php'; ?>
  <label class="felt" for="smsTekst">Tekst, du kan kopiere og sende</label>
  <textarea class="felt" id="smsTekst" rows="3" readonly style="width:100%;font-family:inherit;font-size:1rem"
>Log ind på <?= h($loginAdr) ?> med brugernavnet <?= h($engangsTil) ?> og engangskoden <?= h($engangskode) ?> – du bliver bedt om at vælge din egen adgangskode.</textarea>
</div>
<script>document.getElementById("engangskode").focus();</script>
<?php endif; ?>

<nav class="hop" aria-label="Gå til et afsnit">
  <span>Gå til:</span>
  <a href="#h-filer">Filer</a>
  <a href="#h-brugere">Brugere</a>
  <a href="#h-titel">Overskrift</a>
  <a href="#h-vedl">Vedligeholdelse</a>
  <a href="#h-log">Hvem har gjort hvad</a>
</nav>

<?php if ($ukendte || $forsvundne): ?>
<div class="besked fejl-b">
  <p><?php
    $dele = [];
    if ($ukendte) $dele[] = $ukendte . ($ukendte === 1 ? ' fil ligger' : ' filer ligger') . ' i mappen uden at være med på søgesiden';
    if ($forsvundne) $dele[] = $forsvundne . ($forsvundne === 1 ? ' post peger' : ' poster peger') . ' på filer, der ikke findes mere';
    echo h(implode(', og ', $dele)) . '.';
  ?> Tryk på <strong>Byg søgesiden forfra</strong> nederst for at rette det.</p>
</div>
<?php endif; ?>

<section class="panel" aria-labelledby="h-filer">
  <h2 id="h-filer">Filer på søgesiden (<?= count($poster) ?>)</h2>
<?php if (!$poster): ?>
  <p class="tomt">Der er ingen filer endnu. <a href="upload.php">Læg filer op</a></p>
<?php else: ?>
  <div class="raekke" style="margin-bottom:.8rem">
    <div>
      <label class="felt" for="adminSoeg">Find en fil</label>
      <input class="felt" id="adminSoeg" type="search" placeholder="Titel, filnavn, udgiver eller hvem der lagde den op">
    </div>
    <div style="flex:0 1 16rem">
      <label class="felt" for="adminSort">Rækkefølge</label>
      <select class="felt" id="adminSort">
        <option value="titel">Titel A–Å</option>
        <option value="nyeste">Senest lagt op først</option>
        <option value="stoerst">Største filer først</option>
      </select>
    </div>
  </div>
  <p class="hint" id="adminAntal" role="status" aria-live="polite"></p>
  <details class="filvis" id="filVis">
  <summary>Vis alle <?= count($poster) ?> filer</summary>
  <ul class="liste" id="adminListe">
<?php foreach ($poster as $navn => $p):
        $l = $log[$navn] ?? null;
        $af = $l ? ('Lagt op af ' . $l['af'] . ', ' . dansk_tid((int)$l['tid'])) : 'Lagt op udenom, fx med FTP';
        $meta = array_filter([$navn, $p['type'] ?: null, $p['publisher'] ?: null, menneske_str((int)($p['storrelse'] ?? 0))]);
        $soeg = mb_strtolower($p['title'] . ' ' . $navn . ' ' . $p['publisher'] . ' ' . ($l['af'] ?? ''), 'UTF-8'); ?>
    <li data-soeg="<?= h($soeg) ?>" data-titel="<?= h(mb_strtolower($p['title'], 'UTF-8')) ?>"
        data-tid="<?= (int)($l['tid'] ?? 0) ?>" data-str="<?= (int)($p['storrelse'] ?? 0) ?>">
      <span class="hoved">
        <a class="t" href="filer/<?= h(rawurlencode($navn)) ?>" target="_blank" rel="noopener"><?= h($p['title']) ?></a><?= $p['link'] !== '' ? ' <span class="m" style="display:inline">↗ henviser videre</span>' : '' ?>
        <span class="m"><?= h(implode(' · ', $meta)) ?></span>
        <span class="m"><?= h($af) ?></span>
      </span>
      <form method="post" action="admin.php" data-titel="<?= h($p['title']) ?>" class="sletform">
        <?= csrf_felt() ?>
        <input type="hidden" name="handling" value="slet">
        <input type="hidden" name="fil" value="<?= h($navn) ?>">
        <button class="slet" type="submit" aria-label="Slet <?= h($p['title']) ?>">Slet</button>
      </form>
    </li>
<?php endforeach; ?>
  </ul>
  </details>
<?php endif; ?>
</section>

<?php if ($indeks['sprunget']): ?>
<section class="panel" aria-labelledby="h-sprunget">
  <h2 id="h-sprunget">Filer der ikke er med på søgesiden (<?= count($indeks['sprunget']) ?>)</h2>
  <p>De ligger i mappen, men mangler metadata. De er sandsynligvis lagt op med FTP.</p>
  <ul class="liste">
<?php foreach ($indeks['sprunget'] as $navn => $grund): ?>
    <li>
      <span class="hoved">
        <a class="t" href="filer/<?= h(rawurlencode($navn)) ?>" target="_blank" rel="noopener"><?= h($navn) ?></a>
        <span class="m"><?= h(ucfirst((string)$grund)) ?></span>
      </span>
      <form method="post" action="admin.php" data-titel="<?= h($navn) ?>" class="sletform">
        <?= csrf_felt() ?>
        <input type="hidden" name="handling" value="slet">
        <input type="hidden" name="fil" value="<?= h($navn) ?>">
        <button class="slet" type="submit" aria-label="Slet <?= h($navn) ?>">Slet</button>
      </form>
    </li>
<?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="panel" aria-labelledby="h-brugere">
  <h2 id="h-brugere">Brugere</h2>
  <p class="hint"><strong>Superadmins</strong> kan alt og står i config.php. <strong>Redaktører</strong>
  kan lægge filer op, sætte prikker i arbejdslisten og skifte deres egen adgangskode, men ikke slette filer eller styre brugere.</p>
  <ul class="liste">
<?php foreach (array_keys(config_brugere()) as $navn): ?>
    <li>
      <span class="hoved"><span class="t"><?= h($navn) ?></span>
        <span class="m"><?= er_admin($navn) ? 'Superadmin' : 'Redaktør' ?> · står i config.php</span></span>
    </li>
<?php endforeach; ?>
<?php foreach (redaktoerer() as $navn => $r):
        if (isset(config_brugere()[$navn])) continue;
        $status = empty($r['aktiv']) ? 'Lukket' . (!empty($r['lukket']) ? ' ' . dansk_tid((int)$r['lukket'], false) : '')
                : (!empty($r['skal_skifte']) ? 'Har endnu ikke valgt sin egen adgangskode' : 'Aktiv'); ?>
    <li>
      <span class="hoved"><span class="t"><?= h($navn) ?></span>
        <span class="m">Redaktør · <?= h($status) ?> · oprettet <?= h(dansk_tid((int)($r['oprettet'] ?? 0), false)) ?><?= !empty($r['oprettet_af']) ? ' af ' . h($r['oprettet_af']) : '' ?></span></span>
      <span style="display:flex;gap:.5rem;flex-wrap:wrap">
<?php if (!empty($r['aktiv'])): ?>
        <form method="post" action="admin.php"><?= csrf_felt() ?><input type="hidden" name="handling" value="ny_kode"><input type="hidden" name="navn" value="<?= h($navn) ?>">
          <button class="rolig" type="submit" aria-label="Lav ny engangskode til <?= h($navn) ?>">Ny engangskode</button></form>
        <form method="post" action="admin.php" class="lukform" data-navn="<?= h($navn) ?>"><?= csrf_felt() ?><input type="hidden" name="handling" value="luk"><input type="hidden" name="navn" value="<?= h($navn) ?>">
          <button class="slet" type="submit" aria-label="Luk <?= h($navn) ?>">Luk</button></form>
<?php else: ?>
        <form method="post" action="admin.php"><?= csrf_felt() ?><input type="hidden" name="handling" value="genaabn"><input type="hidden" name="navn" value="<?= h($navn) ?>">
          <button class="rolig" type="submit" aria-label="Genåbn <?= h($navn) ?>">Genåbn</button></form>
<?php endif; ?>
      </span>
    </li>
<?php endforeach; ?>
  </ul>

  <form method="post" action="admin.php" style="margin-top:1.2rem">
    <?= csrf_felt() ?>
    <input type="hidden" name="handling" value="ny_bruger">
    <div class="raekke">
      <div>
        <label class="felt" for="nyNavn">Opret ny redaktør – brugernavn</label>
        <input class="felt" id="nyNavn" name="navn" type="text" maxlength="30" autocapitalize="none"
               spellcheck="false" placeholder="fx bente" required pattern="[a-zæøå0-9][a-zæøå0-9._\-]{1,29}">
      </div>
      <div style="flex:0 0 auto"><button class="stor" type="submit">Opret redaktør</button></div>
    </div>
    <p class="hint">Der laves en engangskode, som du giver videre. Små bogstaver, tal, punktum og bindestreg.</p>
  </form>
</section>

<section class="panel" aria-labelledby="h-titel">
  <h2 id="h-titel">Overskrift på søgesiden</h2>
  <form method="post" action="admin.php">
    <?= csrf_felt() ?>
    <input type="hidden" name="handling" value="titel">
    <label class="felt" for="titel">Overskrift</label>
    <input class="felt" id="titel" name="titel" type="text" maxlength="150" value="<?= h($ind['titel']) ?>">
    <label class="felt" for="undertitel">Linje under overskriften (kan stå tom)</label>
    <input class="felt" id="undertitel" name="undertitel" type="text" maxlength="200"
           placeholder="fx foreningens navn" value="<?= h($ind['undertitel']) ?>">
    <p style="margin:1rem 0 0"><button class="stor" type="submit">Gem overskrift</button></p>
  </form>
</section>

<section class="panel" aria-labelledby="h-vedl">
  <h2 id="h-vedl">Vedligeholdelse</h2>
  <form method="post" action="admin.php">
    <?= csrf_felt() ?>
    <input type="hidden" name="handling" value="genopbyg">
    <button class="rolig" type="submit">Byg søgesiden forfra</button>
    <p class="hint">Læser alle filer i mappen igen og laver små billeder til de filer, der mangler dem.
    Brug den, hvis der er lagt filer op eller slettet filer med FTP – og én gang efter opdateringen,
    så de filer, der allerede ligger her, også får billeder.<?php
      if (!function_exists('imagecreatefromstring')) echo ' <strong>Webhotellet har ikke GD, så der kan ikke laves billeder.</strong>';
    ?></p>
  </form>

  <form method="post" action="admin.php" style="margin-top:1.3rem" id="toemForm">
    <?= csrf_felt() ?>
    <input type="hidden" name="handling" value="toem">
    <button class="rolig" type="submit" <?= $kurv['antal'] ? '' : 'disabled' ?>>Tøm papirkurven</button>
    <p class="hint"><?= $kurv['antal']
        ? 'Der ligger ' . $kurv['antal'] . ($kurv['antal'] === 1 ? ' slettet fil' : ' slettede filer')
          . ' (' . h(menneske_str($kurv['bytes'])) . ') i data/papirkurv/. De kan hentes tilbage med FTP, indtil papirkurven tømmes.'
        : 'Papirkurven er tom.' ?></p>
  </form>

  <p class="hint" style="margin-top:1.3rem">Mappen <strong>filer/</strong> indeholder altid en færdig
  <strong>soegeside.html</strong>. Kopierer du hele mappen – også undermappen <strong>thumbs/</strong> med de små
  billeder – til et USB-stik, har du et arkiv, der virker uden internet.</p>
</section>

<section class="panel" aria-labelledby="h-log">
  <h2 id="h-log">Hvem har gjort hvad</h2>
<?php $haend = seneste_haendelser(40); if (!$haend): ?>
  <p class="tomt">Der er ikke sket noget endnu.</p>
<?php else: ?>
  <div class="logramme">
  <table class="log">
    <thead><tr><th scope="col">Tidspunkt</th><th scope="col">Bruger</th><th scope="col">Hændelse</th></tr></thead>
    <tbody>
<?php foreach ($haend as $e): ?>
      <tr><td style="white-space:nowrap"><?= h($e['tid']) ?></td><td><?= h($e['bruger']) ?></td><td><?= h($e['tekst']) ?></td></tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>
</section>

<script>
document.querySelectorAll(".sletform").forEach(f => f.addEventListener("submit", e => {
  if (!confirm("Slet \"" + f.dataset.titel + "\"?\n\nFilen fjernes fra søgesiden og lægges i papirkurven.")) e.preventDefault();
}));
document.querySelectorAll(".lukform").forEach(f => f.addEventListener("submit", e => {
  if (!confirm("Luk " + f.dataset.navn + "?\n\nBrugeren kan ikke logge ind, før du åbner den igen.")) e.preventDefault();
}));
const toem = document.getElementById("toemForm");
if (toem) toem.addEventListener("submit", e => {
  if (!confirm("Tøm papirkurven?\n\nDe slettede filer forsvinder for altid.")) e.preventDefault();
});

(function(){
  const liste = document.getElementById("adminListe");
  if (!liste) return;
  const soeg = document.getElementById("adminSoeg");
  const sort = document.getElementById("adminSort");
  const antal = document.getElementById("adminAntal");
  const fold = document.getElementById("filVis");
  const alle = Array.from(liste.children);

  /* Alle ord skal findes, men ikke nødvendigvis ved siden af hinanden. */
  function vis(){
    const q = soeg.value.trim().toLowerCase();
    const ord = q.split(/\s+/).filter(Boolean);
    const s = sort.value;
    const raekker = alle.slice().sort((a, b) => {
      if (s === "nyeste") return b.dataset.tid - a.dataset.tid;
      if (s === "stoerst") return b.dataset.str - a.dataset.str;
      return a.dataset.titel.localeCompare(b.dataset.titel, "da");
    });
    let n = 0;
    raekker.forEach(li => {
      const med = ord.every(w => li.dataset.soeg.indexOf(w) !== -1);
      li.hidden = !med;
      if (med) n++;
      liste.appendChild(li);
    });
    antal.textContent = q ? (n ? "Viser " + n + " af " + alle.length + " filer." : "Ingen filer passer til søgningen.") : "";
    if (q) fold.open = true;
  }
  soeg.addEventListener("input", vis);
  sort.addEventListener("change", vis);
  vis();
})();
</script>
<?php side_bund();
