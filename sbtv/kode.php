<?php
/* =========================================================================
   Søgeindeks · Online — skift adgangskode
   Redaktører skifter selv deres kode her. Har de fået en engangskode af en
   superadmin, sendes de hertil første gang, de logger ind.
   Superadmins står i config.php, og deres kode rettes der.
   ========================================================================= */

require __DIR__ . '/faelles.php';
$bruger = kraev_login();

$besked = '';
$fejl = '';
$redaktoer = er_redaktoer_fra_admin($bruger);
$tvungen = skal_skifte_kode($bruger);

if ($redaktoer && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gammel = (string)($_POST['gammel'] ?? '');
    $ny = (string)($_POST['ny'] ?? '');
    $igen = (string)($_POST['igen'] ?? '');

    if (!csrf_ok()) {
        $fejl = 'Siden var udløbet. Prøv igen.';
    } elseif (!tjek_kode($bruger, $gammel)) {
        sleep(1);
        $fejl = 'Den nuværende adgangskode passer ikke.';
    } elseif (mb_strlen($ny, 'UTF-8') < 10) {
        $fejl = 'Den nye adgangskode skal være på mindst 10 tegn.';
    } elseif ($ny !== $igen) {
        $fejl = 'De to nye adgangskoder er ikke ens.';
    } elseif ($ny === $gammel) {
        $fejl = 'Den nye adgangskode skal være en anden end den gamle.';
    } elseif (mb_strtolower($ny, 'UTF-8') === $bruger) {
        $fejl = 'Adgangskoden må ikke være det samme som brugernavnet.';
    } else {
        $ok = med_laas(function () use ($bruger, $ny) {
            $r = redaktoerer();
            if (!isset($r[$bruger]) || empty($r[$bruger]['aktiv'])) return false;
            $r[$bruger]['hash'] = lav_hash($ny);
            $r[$bruger]['skal_skifte'] = false;
            $r[$bruger]['kode_skiftet'] = time();
            return skriv_json(BRUGER_FIL, $r);
        });
        if ($ok) {
            session_regenerate_id(true);
            log_haendelse($bruger, 'skiftede sin adgangskode');
            if ($tvungen) {
                header('Location: upload.php');
                exit;
            }
            $besked = 'Din nye adgangskode er gemt.';
        } else {
            $fejl = 'Adgangskoden kunne ikke gemmes. Prøv igen, eller spørg en superadmin.';
        }
    }
}

$ind = indstillinger();
side_top('Skift adgangskode', $ind['titel'], $bruger, 'kode');
?>
<section class="panel" aria-labelledby="h-kode" style="max-width:34rem">
  <h2 id="h-kode"><?= $tvungen ? 'Vælg din egen adgangskode' : 'Skift adgangskode' ?></h2>

<?php if ($besked !== ''): ?>
  <div class="besked" role="status"><p><?= h($besked) ?></p></div>
<?php endif; ?>
<?php if ($fejl !== ''): ?>
  <div class="besked fejl-b" role="alert"><p><?= h($fejl) ?></p></div>
<?php endif; ?>

<?php if (!$redaktoer): ?>
  <p>Du er superadmin. Din adgangskode står i <strong>config.php</strong> på webhotellet.
  Lav en ny hash med <code>tools/password-hash.html</code> eller <code>lav-hash.php</code>,
  og skift linjen ud i config.php.</p>
<?php else: ?>
  <?php if ($tvungen): ?>
  <p>Du er logget ind med en engangskode. Vælg nu din egen adgangskode, før du fortsætter.
  Kun du kender den.</p>
  <?php endif; ?>
  <form method="post" action="kode.php">
    <?= csrf_felt() ?>
    <label class="felt" for="gammel"><?= $tvungen ? 'Engangskoden' : 'Nuværende adgangskode' ?></label>
    <input class="felt" id="gammel" name="gammel" type="password" autocomplete="current-password" required>
    <label class="felt" for="ny">Ny adgangskode (mindst 10 tegn)</label>
    <input class="felt" id="ny" name="ny" type="password" autocomplete="new-password" minlength="10" required>
    <label class="felt" for="igen">Skriv den nye adgangskode igen</label>
    <input class="felt" id="igen" name="igen" type="password" autocomplete="new-password" minlength="10" required>
    <p class="hint">Et godt råd: Brug tre-fire almindelige ord efter hinanden, fx <em>gul cykel ved havnen</em>.
    Det er let at huske og svært at gætte.</p>
    <p style="margin:1.1rem 0 0"><button class="stor" type="submit">Gem adgangskoden</button></p>
  </form>
<?php endif; ?>
</section>
<?php side_bund();
