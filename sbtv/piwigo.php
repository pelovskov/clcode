<?php
/* =========================================================================
   Søgeindeks · Online — arbejdsliste over Piwigo-galleriet
   Viser alle offentlige album i galleriet med fælles prikker, så man kan se,
   hvilke album der er reddet ud som singlefile-album eller galleriarkiv.
     GET  piwigo.php           siden (kræver login)
     GET  piwigo.php?status    prikkerne som JSON, og hvilke album der har et
                               album i søgeindekset (de er altid grønne)
     POST piwigo.php           sæt en prik: id, s (roed, gul, groen eller tom), csrf
   Selve listen over album hentes af browseren direkte fra galleriets
   grænseflade (ws.php), uden login i galleriet, så kun offentlige album ses.
   Galleriet skal ligge på samme webadresse som søgeportalen.
   Knapperne åbner Piwigo Albumbygger og Galleriarkiv med ?album=NUMMER.
   ========================================================================= */

require __DIR__ . '/faelles.php';

$erApi = isset($_GET['status']) || $_SERVER['REQUEST_METHOD'] === 'POST';

function pw_json(array $d, int $kode = 200): void
{
    http_response_code($kode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

$bruger = aktuel_bruger();
if ($bruger === null) {
    if ($erApi) pw_json(['ok' => false, 'fejl' => 'Du er ikke logget ind mere. Genindlæs siden og log ind igen.'], 401);
    kraev_login();
}
if (skal_skifte_kode($bruger)) {
    if ($erApi) pw_json(['ok' => false, 'fejl' => 'Vælg først din egen adgangskode under «Skift adgangskode».'], 403);
    header('Location: kode.php');
    exit;
}

/* ---- Prikkerne som JSON ---- */
if (isset($_GET['status']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    /* Et album lavet med Piwigo Albumbygger har albummets adresse i feltet
       Kilde (dc.source), fx …/index.php?/category/25. Ligger det i søgeindekset,
       er albummet reddet, og prikken er altid grøn. */
    $auto = [];
    foreach (hent_indeks()['poster'] as $p) {
        $kilde = (string)($p['source'] ?? '');
        if (preg_match('#category/(\d+)#', $kilde, $m)) $auto[$m[1]] = true;
    }
    $status = laes_json(PIWIGO_STATUS, []);
    pw_json(['ok' => true, 'status' => (object)$status, 'auto' => array_map('strval', array_keys($auto))]);
}

/* ---- Sæt en prik ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) pw_json(['ok' => false, 'fejl' => 'Siden var udløbet. Genindlæs siden, og prøv igen.'], 400);
    $id = (string)($_POST['id'] ?? '');
    $s = (string)($_POST['s'] ?? '');
    if (!preg_match('/^[0-9]{1,9}$/', $id) || !in_array($s, ['', 'roed', 'gul', 'groen'], true)) {
        pw_json(['ok' => false, 'fejl' => 'Ugyldigt album eller farve.'], 400);
    }
    $post = med_laas(function () use ($id, $s, $bruger) {
        $alle = laes_json(PIWIGO_STATUS, []);
        if ($s === '') unset($alle[$id]);
        else $alle[$id] = ['s' => $s, 'af' => $bruger, 'tid' => time()];
        if (!skriv_json(PIWIGO_STATUS, $alle)) return false;
        return $alle[$id] ?? null;
    });
    if ($post === false) pw_json(['ok' => false, 'fejl' => 'Prikken kunne ikke gemmes. Tjek rettighederne på mappen data/.'], 500);
    pw_json(['ok' => true, 'post' => $post]);
}

/* ---- Siden ----
   Galleriets adresse på webhotellet. Standard er roden («/»), fx
   https://sfbbilleder.eu/ws.php. Kan rettes med $PIWIGO_ADRESSE i config.php.
   Kun en sti på samme websted er tilladt. */
$base = (string)($PIWIGO_ADRESSE ?? '/');
if (!preg_match('#^/([A-Za-z0-9_\-.]+/)*$#', $base) || strpos($base, '..') !== false) $base = '/';

$jsonFlag = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$konfig = ['api' => 'piwigo.php', 'csrf' => csrf(), 'base' => $base];

header('Cache-Control: no-store');
side_top('Galleriet', 'Arbejdsliste: hvilke album i Piwigo er reddet ud som album eller galleriarkiv?', $bruger, 'piwigo');
?>
<style>
.pw-tal{display:flex;flex-wrap:wrap;gap:.4rem 1.2rem;align-items:baseline;margin:0 0 .5rem;font-size:1.05rem}
.pw-tal strong{font-size:1.6rem;color:var(--footer-strong)}
.pw-bar{height:1.1rem;background:var(--paper-dim);border-radius:6px;overflow:hidden;display:flex}
.pw-bar span{display:block;height:100%}
.pw-bar .g{background:#2e7d32}.pw-bar .y{background:#f2b705}
.pw-filter{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin:.8rem 0 0}
.pw-filter button{font-family:inherit;font-size:1rem;background:#fff;color:var(--ink);border:2px solid var(--line);border-radius:999px;min-height:44px;padding:.3rem .9rem;cursor:pointer}
.pw-filter button[aria-pressed="true"]{background:var(--accent);border-color:var(--accent);color:#fff}
.pw-filter input,.pw-filter select{font-family:inherit;font-size:1rem;border:2px solid var(--line);border-radius:8px;min-height:44px;padding:.3rem .6rem;background:#fff;color:var(--ink)}
.pw-filter input{flex:1 1 14rem}
.pw-liste{list-style:none;margin:0;padding:0}
.pw-liste li{display:flex;gap:.8rem;align-items:center;padding:.6rem .5rem;border-bottom:1px solid var(--line)}
.pw-liste li[hidden]{display:none}
.pw-dot{flex:0 0 auto;width:40px;height:40px;border-radius:50%;border:3px solid #fff;background:#cfc6cb;box-shadow:0 1px 5px rgba(0,0,0,.35);cursor:pointer;padding:0}
.pw-dot[data-s=roed]{background:#c62828}.pw-dot[data-s=gul]{background:#f2b705}.pw-dot[data-s=groen]{background:#2e7d32}
.pw-dot[data-auto]{cursor:default}
.pw-th{flex:0 0 auto;width:72px;height:54px;border-radius:6px;object-fit:cover;background:var(--paper-dim)}
.pw-hoved{flex:1 1 auto;min-width:0}
.pw-hoved .t{font-weight:bold;color:var(--footer-strong);word-break:break-word}
.pw-hoved .t a{color:inherit}
.pw-hoved .m{display:block;font-size:.95rem;color:var(--muted)}
.pw-hoved .adv{display:block;font-size:.92rem;color:var(--advarsel)}
.pw-knap{display:flex;flex-wrap:wrap;gap:.4rem;flex:0 0 auto}
.pw-knap a{display:inline-flex;align-items:center;min-height:44px;padding:.3rem .9rem;border:2px solid var(--accent);border-radius:999px;font-size:.98rem;font-weight:bold;text-decoration:none}
.pw-knap a:hover{background:var(--accent);color:#fff}
@media(max-width:640px){.pw-liste li{flex-wrap:wrap}.pw-th{display:none}}
</style>

<section class="panel" aria-labelledby="pw-h-frem">
  <h2 id="pw-h-frem">Hvor langt er vi?</h2>
  <div class="pw-tal" id="pw-tal" aria-live="polite">Henter galleriets album …</div>
  <div class="pw-bar" aria-hidden="true"><span class="g" id="pw-bar-g" style="width:0"></span><span class="y" id="pw-bar-y" style="width:0"></span></div>
  <p class="hint">Grøn = reddet (albummet ligger i søgeindekset, eller nogen har sat prikken). Gul = i gang. Rød = bør reddes. Grå = ikke vurderet. Tryk på prikken for at skifte farve.</p>
</section>

<section class="panel" aria-labelledby="pw-h-liste">
  <h2 id="pw-h-liste">Albummene</h2>
  <div class="pw-filter">
    <input type="search" id="pw-q" placeholder="Søg i albummenes navne" aria-label="Søg i albummenes navne">
    <select id="pw-sort" aria-label="Sortering">
      <option value="galleri">Som i galleriet</option>
      <option value="stoerst">Flest billeder først</option>
      <option value="az">A–Å</option>
    </select>
  </div>
  <div class="pw-filter" role="group" aria-label="Vis">
    <button type="button" data-f="alle" aria-pressed="true">Alle</button>
    <button type="button" data-f="ikke" aria-pressed="false">Ikke reddet</button>
    <button type="button" data-f="gul" aria-pressed="false">I gang</button>
    <button type="button" data-f="groen" aria-pressed="false">Reddet</button>
  </div>
  <p class="hint" id="pw-antal"></p>
  <ul class="pw-liste" id="pw-liste"></ul>
</section>

<script>
(function(){
  "use strict";
  const K = <?= json_encode($konfig, $jsonFlag) ?>;
  const RÆKKE = ['', 'roed', 'gul', 'groen'];
  const NAVN = { '': 'ikke vurderet', roed: 'rød, bør reddes', gul: 'gul, i gang', groen: 'grøn, reddet' };
  const STOR = 300;   // over så mange billeder: bedre som galleriarkiv end som ét album
  let album = [], status = {}, auto = {}, filter = 'alle';

  const $ = id => document.getElementById(id);
  function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
  function tekst(s){ const d = document.createElement('div'); d.innerHTML = String(s || ''); return (d.textContent || '').trim(); }
  function tal(n){ return Number(n || 0).toLocaleString('da-DK'); }
  function farve(a){ return auto[a.id] ? 'groen' : ((status[a.id] && status[a.id].s) || ''); }

  async function hentAlbum(){
    const q = new URLSearchParams({ format:'json', method:'pwg.categories.getList', recursive:'true', public:'true', fullname:'false' });
    // Uden login i galleriet (credentials: omit), så kun offentlige album kommer med.
    const r = await fetch(K.base + 'ws.php?' + q.toString(), { credentials:'omit', cache:'no-store' });
    if (!r.ok) throw new Error('Galleriet svarede ikke (' + r.status + ').');
    const j = await r.json();
    if (j.stat !== 'ok') throw new Error('Galleriet gav en fejl: ' + (j.message || 'ukendt'));
    return (j.result.categories || [])
      .filter(c => !c.status || c.status === 'public')
      .map(c => ({
        id: String(c.id),
        navn: tekst(c.name) || 'Album ' + c.id,
        egne: Number(c.nb_images || 0),
        ialt: Number(c.total_nb_images || c.nb_images || 0),
        under: Number(c.nb_categories || 0),
        dybde: String(c.uppercats || c.id).split(',').length - 1,
        rang: String(c.global_rank || ''),
        thumb: c.tn_url || '',
        sidst: c.date_last ? String(c.date_last).slice(0, 10) : ''
      }));
  }
  async function hentStatus(){
    const r = await fetch(K.api + '?status', { credentials:'same-origin', cache:'no-store' });
    const j = await r.json();
    if (!j.ok) throw new Error(j.fejl || 'Prikkerne kunne ikke hentes.');
    status = j.status || {};
    auto = {}; (j.auto || []).forEach(id => { auto[String(id)] = true; });
  }

  function rangNøgle(r){ return r.split('.').map(x => ('0000' + x).slice(-5)).join('.'); }
  function sorteret(){
    const l = album.slice(), s = $('pw-sort').value;
    if (s === 'stoerst') l.sort((a, b) => b.egne - a.egne || a.navn.localeCompare(b.navn, 'da'));
    else if (s === 'az') l.sort((a, b) => a.navn.localeCompare(b.navn, 'da'));
    else l.sort((a, b) => rangNøgle(a.rang).localeCompare(rangNøgle(b.rang)));
    return l;
  }

  function række(a){
    const f = farve(a), adr = K.base + 'index.php?/category/' + a.id;
    const meta = [a.egne ? tal(a.egne) + ' billeder' : 'ingen billeder i selve albummet'];
    if (a.under) meta.push(a.under + ' underalbum, ' + tal(a.ialt) + ' billeder i alt');
    if (a.sidst) meta.push('senest ' + a.sidst);
    const knapper = [];
    if (a.egne) knapper.push('<a href="' + esc(K.base + 'piwigo-album-bygger.html?album=' + a.id) + '" target="_blank" rel="noopener">Lav album</a>');
    if (a.ialt) knapper.push('<a href="' + esc(K.base + 'galleriarkiv.html?album=' + a.id) + '" target="_blank" rel="noopener">Gem som galleriarkiv</a>');
    const indryk = $('pw-sort').value === 'galleri' ? ' style="padding-left:' + (0.5 + a.dybde * 1.6) + 'rem"' : '';
    return '<li data-id="' + esc(a.id) + '"' + indryk + '>'
      + '<button type="button" class="pw-dot" data-s="' + f + '"' + (auto[a.id] ? ' data-auto="1"' : '')
      + ' aria-label="' + esc(a.navn + ': ' + (auto[a.id] ? 'grøn, ligger i søgeindekset' : NAVN[f] + '. Tryk for at skifte')) + '"></button>'
      + (a.thumb ? '<img class="pw-th" src="' + esc(a.thumb) + '" alt="" loading="lazy">' : '<span class="pw-th" aria-hidden="true"></span>')
      + '<div class="pw-hoved"><span class="t"><a href="' + esc(adr) + '" target="_blank" rel="noopener">' + esc(a.navn) + '</a></span>'
      + '<span class="m">' + esc(meta.join(' · ')) + (auto[a.id] ? ' · i søgeindekset' : '') + '</span>'
      + (a.egne > STOR ? '<span class="adv">Stort album: bedre som galleriarkiv end som ét album.</span>' : '')
      + '</div><div class="pw-knap">' + knapper.join('') + '</div></li>';
  }

  function tegn(){
    const q = $('pw-q').value.trim().toLowerCase();
    const l = sorteret().filter(a => {
      const f = farve(a);
      if (filter === 'ikke' && f === 'groen') return false;
      if (filter === 'gul' && f !== 'gul') return false;
      if (filter === 'groen' && f !== 'groen') return false;
      return !q || a.navn.toLowerCase().indexOf(q) !== -1;
    });
    $('pw-liste').innerHTML = l.map(række).join('') || '<li class="tomt">Ingen album passer.</li>';
    $('pw-antal').textContent = 'Viser ' + l.length + ' af ' + album.length + ' album.';
    tegnFremskridt();
  }

  function tegnFremskridt(){
    const med = album.filter(a => a.egne > 0);
    const billeder = med.reduce((s, a) => s + a.egne, 0);
    const g = med.filter(a => farve(a) === 'groen'), y = med.filter(a => farve(a) === 'gul');
    const gb = g.reduce((s, a) => s + a.egne, 0), yb = y.reduce((s, a) => s + a.egne, 0);
    $('pw-tal').innerHTML = '<span><strong>' + g.length + '</strong> af ' + med.length + ' album med billeder er reddet</span>'
      + '<span><strong>' + tal(gb) + '</strong> af ' + tal(billeder) + ' billeder</span>'
      + (y.length ? '<span>' + y.length + ' album er i gang</span>' : '');
    $('pw-bar-g').style.width = (billeder ? gb / billeder * 100 : 0) + '%';
    $('pw-bar-y').style.width = (billeder ? yb / billeder * 100 : 0) + '%';
  }

  async function sætPrik(id, s){
    const fd = new FormData(); fd.append('id', id); fd.append('s', s); fd.append('csrf', K.csrf);
    const r = await fetch(K.api, { method:'POST', body: fd, credentials:'same-origin' });
    const j = await r.json().catch(() => ({ ok:false, fejl:'Uventet svar fra serveren.' }));
    if (!j.ok) throw new Error(j.fejl || 'Prikken kunne ikke gemmes.');
    if (j.post) status[id] = j.post; else delete status[id];
  }

  $('pw-liste').addEventListener('click', async e => {
    const b = e.target.closest('.pw-dot');
    if (!b || b.dataset.auto) return;
    const id = b.closest('li').dataset.id;
    const nu = (status[id] && status[id].s) || '';
    const ny = RÆKKE[(RÆKKE.indexOf(nu) + 1) % RÆKKE.length];
    b.disabled = true;
    try { await sætPrik(id, ny); tegn(); const nyKnap = document.querySelector('li[data-id="' + id + '"] .pw-dot'); if (nyKnap) nyKnap.focus(); }
    catch (err) { alert(err.message); b.disabled = false; }
  });
  $('pw-q').addEventListener('input', tegn);
  $('pw-sort').addEventListener('change', tegn);
  document.querySelectorAll('[data-f]').forEach(b => b.addEventListener('click', () => {
    filter = b.dataset.f;
    document.querySelectorAll('[data-f]').forEach(x => x.setAttribute('aria-pressed', x === b ? 'true' : 'false'));
    tegn();
  }));

  Promise.all([hentAlbum(), hentStatus()]).then(([a]) => { album = a; tegn(); })
    .catch(err => {
      $('pw-tal').textContent = 'Galleriets album kunne ikke hentes: ' + err.message
        + ' Søgeportalen og galleriet skal ligge på samme webadresse.';
    });
})();
</script>
<?php
side_bund();
