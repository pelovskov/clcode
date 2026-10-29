<?php
/* =========================================================================
   Søgeindeks · Online — statistik
   Viser besøg på forsiden og søgesiden, hvilke historier der bliver åbnet,
   og hvad folk søger efter. Tallene kommer fra data/statistik.json.
   Alle, der er logget ind, kan se siden. Der er ingen personoplysninger.
   ========================================================================= */

require __DIR__ . '/faelles.php';
$bruger = kraev_login();

const MAANEDER = ['januar','februar','marts','april','maj','juni','juli',
                  'august','september','oktober','november','december'];

function maaned_navn(string $ym): string
{
    [$a, $m] = array_map('intval', explode('-', $ym));
    return MAANEDER[$m - 1] . ' ' . $a;
}

function tal(int $n): string
{
    return number_format($n, 0, ',', '.');
}

/* En vandret bjælke til tabellerne. Tallet står altid ved siden af. */
function bjaelke(int $n, int $max): string
{
    $pct = $max > 0 ? max(1, (int)round($n / $max * 100)) : 0;
    return '<span class="bj" aria-hidden="true"><span style="width:' . ($n ? $pct : 0) . '%"></span></span>';
}

$s = hent_statistik();
$poster = hent_indeks()['poster'];
$nu = date('Y-m');

/* ---- Besøg ---- */
$besoegDag = function (string $d) use ($s): int {
    $b = $s['besoeg'][$d] ?? [];
    return (int)($b['forside'] ?? 0) + (int)($b['soeg'] ?? 0);
};
$sumDage = function (int $dage) use ($besoegDag): int {
    $sum = 0;
    for ($i = 0; $i < $dage; $i++) $sum += $besoegDag(date('Y-m-d', strtotime("-$i days")));
    return $sum;
};
$iAlt = 0;
$prMd = [];
foreach ($s['besoeg'] as $d => $b) {
    $m = substr((string)$d, 0, 7);
    $prMd[$m]['forside'] = ($prMd[$m]['forside'] ?? 0) + (int)($b['forside'] ?? 0);
    $prMd[$m]['soeg'] = ($prMd[$m]['soeg'] ?? 0) + (int)($b['soeg'] ?? 0);
    $iAlt += (int)($b['forside'] ?? 0) + (int)($b['soeg'] ?? 0);
}
krsort($prMd);
$prMd = array_slice($prMd, 0, 12, true);
$maxMd = 0;
foreach ($prMd as $b) $maxMd = max($maxMd, $b['forside'] + $b['soeg']);

/* ---- Klik ---- */
$klik = [];
$klikIAlt = 0;
foreach ($s['klik'] as $navn => $md) {
    $alt = array_sum(array_map('intval', $md));
    $klik[] = ['navn' => (string)$navn, 'alt' => $alt, 'md' => (int)($md[$nu] ?? 0), 'post' => $poster[$navn] ?? null];
    $klikIAlt += $alt;
}
usort($klik, fn($a, $b) => [$b['alt'], $b['md']] <=> [$a['alt'], $a['md']]);
$maxKlik = $klik ? $klik[0]['alt'] : 0;

/* Klik pr. forening (udgiver) og pr. emneord. Udgiveren står først i
   emneordene, så den springes over der. */
$prForening = [];
$prEmne = [];
foreach ($klik as $k) {
    if (!$k['post']) continue;
    $udg = trim((string)($k['post']['publisher'] ?? '')) ?: 'Uden udgiver';
    $prForening[$udg] = ($prForening[$udg] ?? 0) + $k['alt'];
    $set = [];
    foreach (preg_split('/\s*;\s*/u', (string)($k['post']['subject'] ?? '')) as $e) {
        $e = trim($e);
        $noegle = mb_strtolower($e, 'UTF-8');
        if ($e === '' || $noegle === mb_strtolower($udg, 'UTF-8') || isset($set[$noegle])) continue;
        $set[$noegle] = true;
        if (!isset($prEmne[$noegle])) $prEmne[$noegle] = ['vis' => $e, 'n' => 0];
        $prEmne[$noegle]['n'] += $k['alt'];
    }
}
arsort($prForening);
uasort($prEmne, fn($a, $b) => $b['n'] <=> $a['n']);
$prEmne = array_slice($prEmne, 0, 20, true);

/* ---- Søgeord ---- */
$ord = [];
$soegIAlt = 0;
foreach ($s['soeg'] as $m => $liste) {
    foreach ($liste as $q => $r) {
        $q = (string)$q;
        if (!isset($ord[$q])) $ord[$q] = ['alt' => 0, 'md' => 0, 'nul' => 0];
        $ord[$q]['alt'] += (int)($r[0] ?? 0);
        $ord[$q]['nul'] += (int)($r[1] ?? 0);
        if ($m === $nu) $ord[$q]['md'] += (int)($r[0] ?? 0);
        $soegIAlt += (int)($r[0] ?? 0);
    }
}
$mest = $ord;
uasort($mest, fn($a, $b) => [$b['alt'], $b['md']] <=> [$a['alt'], $a['md']]);
$mest = array_slice($mest, 0, 30, true);
$udenTraef = array_filter($ord, fn($r) => $r['nul'] > 0);
uasort($udenTraef, fn($a, $b) => $b['nul'] <=> $a['nul']);
$udenTraef = array_slice($udenTraef, 0, 20, true);

$visAlleKlik = isset($_GET['alle']);
$klikVist = $visAlleKlik ? $klik : array_slice($klik, 0, 25);

$ind = indstillinger();
side_top('Statistik', $ind['titel'], $bruger, 'statistik');
?>
<section class="panel" aria-labelledby="h-om">
  <h2 id="h-om">Om tallene</h2>
<?php if (!$s['start']): ?>
  <p>Der er ikke talt noget endnu. Tællingen begynder, når den første besøgende kommer forbi.</p>
<?php else: ?>
  <p>Tællingen begyndte <strong><?= h(dansk_tid($s['start'], false)) ?></strong>.</p>
<?php endif; ?>
  <p class="hint">Tallene er en indikation, ikke en præcis optælling. Søgemaskinernes robotter og
  jeres egne besøg, mens I er logget ind, tælles ikke med. Der gemmes ingen oplysninger om, hvem der
  besøger siden: ingen IP-adresser og ingen cookies. Åbner nogen en fil direkte fra et link, de har
  fået tilsendt, tælles det heller ikke. Kun klik fra forsiden og søgesiden tæller.</p>
  <nav class="hop" aria-label="Gå til">
    <span>Gå til:</span>
    <a href="#h-besoeg">Besøg</a>
    <a href="#h-klik">Mest åbnede</a>
    <a href="#h-forening">Foreninger og emner</a>
    <a href="#h-soeg">Søgeord</a>
  </nav>
</section>

<section class="panel" aria-labelledby="h-besoeg">
  <h2 id="h-besoeg">Besøg på forsiden og søgesiden</h2>
  <ul class="tal4">
    <li><strong><?= tal($besoegDag(date('Y-m-d'))) ?></strong><span>i dag</span></li>
    <li><strong><?= tal($sumDage(7)) ?></strong><span>de seneste 7 dage</span></li>
    <li><strong><?= tal($sumDage(30)) ?></strong><span>de seneste 30 dage</span></li>
    <li><strong><?= tal($iAlt) ?></strong><span>i alt</span></li>
  </ul>
  <p class="hint">Et besøg er én visning af forsiden eller søgesiden. Går den samme person fra
  forsiden til søgesiden, tæller det som to.</p>
<?php if ($prMd): ?>
  <div class="logramme">
  <table class="stat">
    <caption class="visuelt-skjult">Besøg pr. måned, nyeste først</caption>
    <thead><tr><th scope="col">Måned</th><th scope="col" class="n">Forsiden</th><th scope="col" class="n">Søgesiden</th><th scope="col" class="n">I alt</th><th scope="col" class="bjc"><span class="visuelt-skjult">Søjle</span></th></tr></thead>
    <tbody>
<?php foreach ($prMd as $m => $b): $sum = $b['forside'] + $b['soeg']; ?>
      <tr><td><?= h(maaned_navn((string)$m)) ?></td><td class="n"><?= tal($b['forside']) ?></td><td class="n"><?= tal($b['soeg']) ?></td><td class="n"><strong><?= tal($sum) ?></strong></td><td class="bjc"><?= bjaelke($sum, $maxMd) ?></td></tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>
</section>

<section class="panel" aria-labelledby="h-klik">
  <h2 id="h-klik">Mest åbnede historier</h2>
<?php if (!$klik): ?>
  <p class="tomt">Ingen har åbnet en historie endnu.</p>
<?php else: ?>
  <p>Historierne er åbnet <strong><?= tal($klikIAlt) ?></strong> gange i alt.</p>
  <div class="logramme">
  <table class="stat">
    <caption class="visuelt-skjult">De mest åbnede historier</caption>
    <thead><tr><th scope="col" class="n">Nr.</th><th scope="col">Historie</th><th scope="col" class="n"><?= h(MAANEDER[(int)date('n') - 1]) ?></th><th scope="col" class="n">I alt</th><th scope="col" class="bjc"><span class="visuelt-skjult">Søjle</span></th></tr></thead>
    <tbody>
<?php foreach ($klikVist as $i => $k):
    $titel = $k['post'] ? (string)($k['post']['title'] ?? $k['navn']) : $k['navn'];
    $udg = $k['post'] ? (string)($k['post']['publisher'] ?? '') : '';
?>
      <tr>
        <td class="n"><?= $i + 1 ?></td>
        <td><?= h($titel) ?>
          <span class="lille"><?= $k['post'] ? h(trim($udg . ($udg !== '' ? ' · ' : '') . $k['navn'])) : h($k['navn']) . ' · findes ikke mere' ?></span></td>
        <td class="n"><?= tal($k['md']) ?></td>
        <td class="n"><strong><?= tal($k['alt']) ?></strong></td>
        <td class="bjc"><?= bjaelke($k['alt'], $maxKlik) ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php if (count($klik) > 25): ?>
  <p class="hint"><?= $visAlleKlik
      ? '<a href="statistik.php#h-klik">Vis kun de 25 mest åbnede</a>'
      : '<a href="statistik.php?alle=1#h-klik">Vis alle ' . tal(count($klik)) . ' historier</a>' ?></p>
<?php endif; ?>
<?php endif; ?>
</section>

<section class="panel" aria-labelledby="h-forening">
  <h2 id="h-forening">Foreninger og emner</h2>
  <p class="hint" style="margin-top:0">Hvor mange gange historierne er åbnet, lagt sammen pr. udgiver og pr. emneord.
  En historie med flere emneord tæller med under dem alle.</p>
  <div class="to">
    <div>
      <h3>Pr. forening</h3>
<?php if (!$prForening): ?>
      <p class="tomt">Ingen tal endnu.</p>
<?php else: $mx = max($prForening); ?>
      <table class="stat">
        <thead><tr><th scope="col">Udgiver</th><th scope="col" class="n">Åbnet</th></tr></thead>
        <tbody>
<?php foreach ($prForening as $u => $n): ?>
          <tr><td><?= h((string)$u) ?><?= bjaelke($n, $mx) ?></td><td class="n"><?= tal($n) ?></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
<?php endif; ?>
    </div>
    <div>
      <h3>Pr. emneord</h3>
<?php if (!$prEmne): ?>
      <p class="tomt">Ingen tal endnu.</p>
<?php else: $mx = max(array_column($prEmne, 'n')); ?>
      <table class="stat">
        <thead><tr><th scope="col">Emneord</th><th scope="col" class="n">Åbnet</th></tr></thead>
        <tbody>
<?php foreach ($prEmne as $e): ?>
          <tr><td><?= h($e['vis']) ?><?= bjaelke($e['n'], $mx) ?></td><td class="n"><?= tal($e['n']) ?></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
<?php endif; ?>
    </div>
  </div>
</section>

<section class="panel" aria-labelledby="h-soeg">
  <h2 id="h-soeg">Hvad folk søger efter</h2>
<?php if (!$ord): ?>
  <p class="tomt">Ingen har søgt endnu.</p>
<?php else: ?>
  <p>Der er søgt <strong><?= tal($soegIAlt) ?></strong> gange. Store og små bogstaver tælles sammen.</p>
  <div class="to">
    <div>
      <h3>Mest brugte søgeord</h3>
      <table class="stat">
        <thead><tr><th scope="col">Søgeord</th><th scope="col" class="n"><?= h(MAANEDER[(int)date('n') - 1]) ?></th><th scope="col" class="n">I alt</th></tr></thead>
        <tbody>
<?php foreach ($mest as $q => $r): ?>
          <tr><td><a href="index.php?q=<?= h(rawurlencode((string)$q)) ?>"><?= h((string)$q) ?></a></td><td class="n"><?= tal($r['md']) ?></td><td class="n"><strong><?= tal($r['alt']) ?></strong></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div>
      <h3>Søgninger uden træf</h3>
<?php if (!$udenTraef): ?>
      <p class="tomt">Alle søgninger har givet træf.</p>
<?php else: ?>
      <table class="stat">
        <thead><tr><th scope="col">Søgeord</th><th scope="col" class="n">Gange</th></tr></thead>
        <tbody>
<?php foreach ($udenTraef as $q => $r): ?>
          <tr><td><?= h((string)$q) ?></td><td class="n"><?= tal($r['nul']) ?></td></tr>
<?php endforeach; ?>
        </tbody>
      </table>
      <p class="hint">Her kan I se, hvad folk leder efter, men ikke finder. Måske er det en idé til en ny historie,
      eller et emneord, der mangler.</p>
<?php endif; ?>
    </div>
  </div>
<?php endif; ?>
</section>
<?php side_bund();
