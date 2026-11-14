#!/usr/bin/env python3
"""
Opskrift: laver "Lokalhistorier · fri udgave" ud fra de almindelige byggere.

Den frie udgave er til enkeltpersoner, skoler og institutioner, som ikke er
med i den fælles portal. Forskellene fra de almindelige byggere:

- Udgiveren skrives i et almindeligt tekstfelt i stedet for at vælges på en liste.
- Kun tre farvetemaer: Vinter, Sommer og Hindbær.
- Udgiveren sættes ikke ind som første emneord, og identifikatoren har
  intet foreningspræfiks, fx "side-sommerlejren-2026".
- Foreningernes særlige ting er fjernet, fx Sidebyggerens faste serier.
- De fælles emneord (tools/emneord.html) bliver til frie forslag i fri/emneord.html.
- Søgeside · Bygger og de søgesider, den laver, har eget design, vælgbare temaer og
  en knap, der gemmer søgesiden og filerne som én zip-fil.
- Filformatet er det samme, så filerne kan åbnes i begge udgaver.

Kør opskriften igen, når en almindelig bygger er blevet forbedret:

    python3 fri/lav-fri-udgave.py

Den læser byggerne i repoets rod og værktøjerne i tools/ og skriver den
frie udgave i mappen fri/. Ret aldrig i de lavede filer i fri/ i hånden –
ret i opskriften eller i den almindelige bygger, og kør opskriften igen.
Kun index.html (forsiden) og LAES-MIG.md i fri/ er skrevet i hånden.
Til sidst pakkes det hele i lokalhistorier-fri-udgave.zip, som kan hentes
fra forsiden.
"""
import re
import sys
import zipfile
from pathlib import Path

ROD = Path(__file__).resolve().parent.parent
UD = ROD / 'fri'

# De byggere, der laves i fri udgave. Flere kommer til trin for trin.
BYGGERE = ['side-bygger.html', 'album-bygger.html', 'enkeltbillede-bygger.html', 'foer-og-nu-bygger.html',
           'bog-bygger.html', 'lydfortaelling-bygger.html', 'mediekort-bygger.html',
           'praesentation-bygger.html', 'samling-bygger.html', 'vejledning-bygger.html']

THEMES = """const THEMES = {
  vinter: {
    label:'Vinter',
    accent:'#2F5D8A', accentDark:'#244A6E', onAccent:'#FFFFFF',
    paper:'#EEF2F6', surface:'#FFFFFF', ink:'#14202B', body:'#25323D',
    muted:'#5A6772', line:'#D6DEE6', quote:'#3C4A56',
    dPaper:'#10161C', dSurface:'#18212A', dInk:'#EAF0F5', dBody:'#D7E0E8',
    dMuted:'#9DAAB6', dLine:'#2C3946', dQuote:'#B8C5D1'
  },
  sommer: {
    label:'Sommer',
    accent:'#2F7A46', accentDark:'#245F37', onAccent:'#FFFFFF',
    paper:'#FBF7EA', surface:'#FFFFFF', ink:'#1F2418', body:'#2E3427',
    muted:'#5E6553', line:'#E6DFC6', quote:'#434A39',
    dPaper:'#14170F', dSurface:'#1C2016', dInk:'#F2F0E4', dBody:'#E1DFD0',
    dMuted:'#A8AC98', dLine:'#33392A', dQuote:'#C3C6B2'
  },
  hindbaer: {
    label:'Hindbær',
    accent:'#E11D48', accentDark:'#BE123C', onAccent:'#FFFFFF',
    paper:'#F4F4F5', surface:'#FFFFFF', ink:'#18181B', body:'#27272A',
    muted:'#52525B', line:'#E4E4E7', quote:'#3F3F46',
    dPaper:'#18181B', dSurface:'#232327', dInk:'#FAFAFA', dBody:'#E4E4E7',
    dMuted:'#A1A1AA', dLine:'#3F3F46', dQuote:'#D4D4D8'
  }
};"""

VAELGER = [
    ('vinter', 'Vinter', '#2F5D8A', ['#2F5D8A', '#BFD7EA', '#EEF2F6']),
    ('sommer', 'Sommer', '#2F7A46', ['#2F7A46', '#F4C542', '#FBF7EA']),
    ('hindbaer', 'Hindbær', '#E11D48', ['#E11D48', '#18181B', '#F4F4F5']),
]

UDGIVER_FELT = ('<input type="text" id="dcPublisher" '
                'placeholder="fx dit eget navn, din familie eller 7.b, Roskilde Lille Skole">')
UDGIVER_HINT = 'dc.publisher — skriv, hvem der står bag, fx dit navn, din familie, en forening eller en klasse'


def erstat(s, moenster, ny, antal=1, navn='', flags=re.S):
    s2, n = re.subn(moenster, ny, s, flags=flags)
    if antal is not None and n != antal:
        sys.exit(f'{navn}: forventede {antal} træf for {moenster!r}, fandt {n}')
    return s2


def lav(fil):
    s = (ROD / fil).read_text(encoding='utf-8')

    # --- Udgiver: tekstfelt i stedet for liste ---
    if fil == 'foer-og-nu-bygger.html':
        # Før og Nu bygger sine Dublin Core-felter i JavaScript.
        s = erstat(s, r"\}else if\(key === 'publisher'\)\{\n.*?\n    \}else\{",
                   "}else if(key === 'publisher'){\n"
                   "      // Fri udgave: udgiveren skrives som almindelig tekst.\n"
                   "      input = document.createElement('input');\n"
                   "      input.type = 'text';\n"
                   "      input.placeholder = 'fx dit eget navn, din familie eller 7.b, Roskilde Lille Skole';\n"
                   "    }else{", navn=fil)
        s = erstat(s, r"input\.addEventListener\('change', invalidate\);[^\n]*",
                   "input.addEventListener('input', invalidate);\n      input.addEventListener('change', invalidate);", navn=fil)
        s = erstat(s, r"'vælg område, så filen kan filtreres i søgeindekset'",
                   "'skriv, hvem der står bag, fx dit navn, din familie, en forening eller en klasse'", navn=fil)
    else:
        s = erstat(s, r'<select id="dcPublisher">.*?</select>', UDGIVER_FELT, navn=fil)
        s = erstat(s, r'dc\.publisher — vælg område, så filen kan filtreres i søgeindekset', UDGIVER_HINT, navn=fil)
    s = erstat(s, r'(const|var) PUBLISHERS = \[.*?\n\];',
               '// Fri udgave: ingen fast liste over udgivere. Udgiveren skrives i et tekstfelt.\n\\1 PUBLISHERS = [];',
               navn=fil)
    s = erstat(s, r'function ensurePublisherOption\(v\)\{.*?\n\}', 'function ensurePublisherOption(v){}', navn=fil)
    # Udgiveren sættes ikke ind som første emneord.
    s = erstat(s, r'if ?\(dc\.publisher(?: \|\| series)?\)\{', 'if (false){  // fri udgave: udgiveren er ikke et emneord',
               navn=fil)
    # Identifikator uden foreningspræfiks.
    s = erstat(s, r"\(prefix && name\) \? \(prefix \+ '-", "(name) ? ('", navn=fil)

    # --- Temaer: kun Vinter, Sommer og Hindbær ---
    s = erstat(s, r'(?:const|var) THEMES = \{.*?\n\};', THEMES, navn=fil)
    s = (s.replace('THEMES.original', 'THEMES.vinter')
          .replace(": 'original';", ": 'vinter';")
          .replace("theme:'original',", "theme:'vinter',")
          .replace("theme: 'original',", "theme: 'vinter',")
          .replace("data.theme || 'original'", "data.theme || 'vinter'")
          .replace("selectTheme('original');", "selectTheme('vinter');"))
    if "'original'" in s:
        sys.exit(f"{fil}: der står stadig 'original' et sted")
    if fil == 'foer-og-nu-bygger.html':
        # Før og Nu bygger temavælgeren i JavaScript ud fra THEMES og THEME_NAMES.
        s = erstat(s, r'var THEME_NAMES = \{[^\n]*\};', "var THEME_NAMES = { vinter:'Vinter', sommer:'Sommer', hindbaer:'Hindbær' };", navn=fil)
    labels = re.findall(r'( *)<label class="theme-option" data-theme="(\w+)".*?</label>\n', s, re.S)
    if not labels and fil != 'foer-og-nu-bygger.html':
        sys.exit(f'{fil}: fandt ikke temavælgeren')
    indryk = labels[0][0] if labels else ''
    nye = ''
    for noegle, navn, accent, prikker in VAELGER:
        swatches = ''.join(f'<span class="swatch" style="background:{c}"></span>' for c in prikker)
        nye += (f'{indryk}<label class="theme-option" data-theme="{noegle}" style="--theme-accent:{accent}">\n'
                f'{indryk}  <input type="radio" name="theme" value="{noegle}"{" checked" if noegle == "vinter" else ""}>\n'
                f'{indryk}  <div class="swatches">{swatches}</div>\n'
                f'{indryk}  <div class="tname">{navn}</div>\n'
                f'{indryk}</label>\n')
    if labels:
        s = erstat(s, r'( *<label class="theme-option" data-theme="\w+".*?</label>\n)+', nye, navn=fil)

    # --- Foreningernes særlige ting ---
    if fil == 'side-bygger.html':
        s = erstat(s, r"const SERIES = \[.*?\];", "const SERIES = [];  // fri udgave: ingen faste serier", navn=fil)
        s = erstat(s, r'\s*<option value="Gader og veje">Gader og veje</option>'
                      r'\s*<option value="Steder, institutioner og mennesker">Steder, institutioner og mennesker</option>',
                   '', navn=fil)
        s = erstat(s, r'<label for="eyebrowSelect">Mærkat / serie \(valgfri\)</label>',
                   '<label for="eyebrowSelect">Mærkat (valgfri)</label>', navn=fil)
        s = erstat(s, r'<div class="hint">Vises som lille tekst over titlen\. Vælger du en af de to serier.*?</div>',
                   '<div class="hint">Vises som lille tekst over titlen, fx «Min gade» eller «Klassens historie». '
                   'Mærkaten vises kun på siden og bliver ikke sat ind i metadata.</div>', navn=fil)
    s = s.replace('Udgiver / område', 'Udgiver')
    # Eksempler i felterne uden foreningsnavne.
    s = re.sub(r'(id="dc_?[Ii]dentifier"[^>]*placeholder="(?:F\.eks\.|fx) )[A-Z]+-', r'\1', s)
    s = s.replace('© Syd for Banen - Lokalhistorisk Forening', '© 7.b, Roskilde Lille Skole')
    s = s.replace('den der har lavet udsendelsen, fx Roskilde TV', 'den der har lavet udsendelsen, fx dit eget navn')
    s = s.replace('placeholder="F.eks. Foreningen ejer rettighederne. Må ikke gengives uden tilladelse."',
                  'placeholder="F.eks. Klassen ejer rettighederne. Må ikke gengives uden tilladelse."')

    # --- Navn og version ---
    s = erstat(s, r'^<title>([^<]*)</title>', r'<title>\1 · fri udgave</title>', navn=fil, flags=re.M)
    s = erstat(s, r'(· version [0-9.]+)(</span>|</p>)', r'\1 · fri udgave\2', navn=fil)

    (UD / fil).write_text(s, encoding='utf-8')
    print(f'Lavet: fri/{fil}')


# --- Søgesidens eget design i den frie udgave ---
# Designet lægges oven på den almindelige søgeside, så foreningernes udgaver
# ikke ændres. Farverne hentes fra THEMES ovenfor, så de kun står ét sted.
# Teksten ender inde i en JavaScript-skabelon og må derfor ikke indeholde backticks.
def soegeside_temaer():
    ud = ''
    for noegle, _, _, _ in VAELGER:
        blok = re.search(noegle + r": \{(.*?)\n  \}", THEMES, re.S).group(1)
        t = dict(re.findall(r"(\w+):'([^']+)'", blok))
        ud += (f':root[data-tema="{noegle}"]{{--accent:{t["accentDark"]};--accent-lys:{t["accent"]};'
               f'--paper:{t["paper"]};--paper-dim:{t["paper"]};--flade:{t["surface"]};--ink:{t["ink"]};'
               f'--muted:{t["muted"]};--line:{t["line"]};--footer-strong:{t["ink"]}}}\n')
    return ud


SOEGESIDE_FAELLES = """
/* Fri udgave · eget design: tema, skrift og layout. Lægges på af fri/lav-fri-udgave.py. */
""" + soegeside_temaer() + """@media (prefers-contrast:more){
  :root[data-tema]{--ink:#000;--muted:#000;--line:#555}
}
body{font-family:"Avenir Next","Segoe UI","Helvetica Neue",Helvetica,Roboto,"Noto Sans",Arial,sans-serif}
h1{font-weight:700;letter-spacing:-.01em}
header{max-width:none;margin:0;padding:2rem 1.25rem 3.5rem;background:var(--accent);color:#fff;
  border-bottom:.4rem solid var(--accent-lys)}
header h1,header .sub,header .tekststr .lbl{color:#fff}
header :focus-visible{outline-color:#fff}
.topbar{margin:0 auto}
.tekststr button{border-color:#fff}
.tekststr button:hover{border-color:var(--ink)}
.tekststr button[aria-pressed="true"]{background:var(--ink);border-color:#fff;color:#fff}
"""

SOEGESIDE_SIDEN = SOEGESIDE_FAELLES + """.topbar{max-width:calc(1100px - 2.5rem)}
.controls{position:relative;width:calc(100% - 2.5rem);max-width:calc(1100px - 2.5rem);margin:-2rem auto 0;
  padding:1rem;background:var(--flade);border:1px solid var(--line);border-radius:14px;
  box-shadow:0 2px 10px rgba(0,0,0,.1)}
.controls input,.controls select{border-color:var(--muted);border-radius:10px}
.controls input{flex:1 1 100%;min-height:58px;font-size:1.2rem}
.controls select{flex:1 1 11rem}
.count{margin-top:1.2rem}
a.row{background:var(--flade);border:1px solid var(--line);border-radius:12px;
  padding:1.1rem 1.15rem;margin-bottom:.75rem;align-items:center}
a.row:hover{border-color:var(--accent)}
.icon{display:flex;align-items:center;justify-content:center;flex:0 0 2.8rem;height:2.8rem;
  background:var(--paper);border-radius:50%;font-size:1.3rem;line-height:1}
.col-main .t{color:var(--accent);font-weight:700}
.col-type{background:var(--paper);border:1px solid var(--line);border-radius:999px;padding:.15rem .75rem}
footer{border-top:1px solid var(--line);padding-top:1.2rem}
@media(max-width:640px){
  header{padding:1.5rem 1rem 3.25rem}
  .controls{width:calc(100% - 2rem);padding:.75rem}
  .controls input,.controls select{flex:1 1 100%}
  a.row{padding:1rem .8rem;align-items:flex-start}
  .icon{flex:0 0 2.2rem;height:2.2rem;font-size:1.1rem}
}
"""

SOEGESIDE_BYGGER = SOEGESIDE_FAELLES + """.topbar{max-width:calc(1400px - 2.5rem)}
h1 .fri{display:block;font-size:1.05rem;font-weight:600;letter-spacing:0;margin-top:.2rem}
.grid{position:relative;margin:-2rem auto 0}
.panel{background:var(--flade);border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
.panel h2,.trin h3{font-weight:700}
button.stor,button.rolig{border-radius:999px}
button.stor:hover{background:var(--ink);border-color:var(--ink)}
button.stor[disabled]{background:var(--line);border-color:var(--line);color:var(--muted)}
button.rolig{border-color:var(--muted)}
input.felt{border-color:var(--muted);border-radius:10px}
#dropzone{border-color:var(--muted);border-radius:12px}
iframe,.tom{border-radius:12px}
.tom[hidden]{display:none}
.temavalg{display:flex;flex-wrap:wrap;gap:.6rem;border:0;margin:.6rem 0 0;padding:0}
.temaknap{flex:1 1 6.5rem;display:flex;flex-direction:column;gap:.35rem;min-height:48px;padding:.6rem .7rem;
  background:#fff;border:2px solid var(--muted);border-radius:10px;cursor:pointer}
.temaknap:hover{border-color:var(--accent)}
.temaknap.valgt{border-color:var(--accent);box-shadow:inset 0 0 0 2px var(--accent)}
.temaknap .raekke{display:flex;align-items:center;gap:.45rem;font-size:1rem}
.temaknap input{width:1.15rem;height:1.15rem;margin:0;flex:0 0 auto}
.temaknap .prikker{display:flex;gap:.25rem}
.temaknap .prik{width:1.15rem;height:1.15rem;border-radius:50%;border:1px solid rgba(0,0,0,.25)}
"""


def soegeside_temavaelger():
    knapper = ''
    for noegle, navn, _, prikker in VAELGER:
        farver = ''.join(f'<span class="prik" style="background:{c}"></span>' for c in prikker)
        knapper += (f'        <label class="temaknap{" valgt" if noegle == "vinter" else ""}">\n'
                    f'          <span class="raekke"><input type="radio" name="tema" value="{noegle}"'
                    f'{" checked" if noegle == "vinter" else ""}>{navn}</span>\n'
                    f'          <span class="prikker" aria-hidden="true">{farver}</span>\n'
                    f'        </label>\n')
    return ('    <div class="trin">\n      <h3>3. Vælg farver</h3>\n'
            '      <fieldset class="temavalg">\n'
            '        <legend class="visuelt-skjult">Farvetema til søgesiden</legend>\n'
            + knapper +
            '      </fieldset>\n    </div>\n\n')


SOEGESIDE_TEMA_KODE = """/* --- Farvetema (fri udgave) --- */
function valgtTema(){
  const v = document.querySelector('input[name="tema"]:checked');
  return v ? v.value : "vinter";
}
(function(){
  const radioer = Array.from(document.querySelectorAll('input[name="tema"]'));
  function vis(){
    const tema = valgtTema();
    document.documentElement.setAttribute("data-tema", tema);
    radioer.forEach(r => r.closest(".temaknap").classList.toggle("valgt", r.checked));
  }
  let gemt = null;
  try{ gemt = localStorage.getItem("fri-soegeside-tema"); }catch(e){}
  radioer.forEach(r => {
    if (r.value === gemt) r.checked = true;
    r.addEventListener("change", () => {
      vis();
      try{ localStorage.setItem("fri-soegeside-tema", valgtTema()); }catch(e){}
      opdaterForhaandsvisning();
    });
  });
  vis();
})();

"""


# --- Zip-knappen i den frie udgave ---
# Koden står både i byggeren og i den færdige søgeside. Den ender også inde i en
# JavaScript-skabelon og må derfor hverken indeholde backticks eller omvendte skråstreger.
SOEGESIDE_ZIP_KODE = """
/* ---- Zip-fil (fri udgave) ----
   Pakker uden komprimering (standardens 5.7, regel 8). Billeder, lyd og video er
   allerede komprimeret. Filerne læses i bidder, så også store filer kan være med.
   Alt foregår i browseren. Der sendes ikke noget nogen steder hen. */
const ZIP_TABEL = (function(){
  const t = [];
  for(let n = 0; n < 256; n++){
    let c = n;
    for(let k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
    t[n] = c >>> 0;
  }
  return t;
})();

/* Kontroltal for en fil. Zip-formatet kræver det, og udpakningen bruger det. */
async function zipKontrol(file){
  const BID = 4 * 1024 * 1024;
  let crc = 0xFFFFFFFF;
  for(let i = 0; i < file.size; i += BID){
    const b = new Uint8Array(await file.slice(i, i + BID).arrayBuffer());
    for(let j = 0; j < b.length; j++) crc = ZIP_TABEL[(crc ^ b[j]) & 255] ^ (crc >>> 8);
  }
  return (crc ^ 0xFFFFFFFF) >>> 0;
}

/* Kun almindelige relative stier må komme med, aldrig fx ../ eller C: */
function zipSikkerSti(s){
  s = String(s || "");
  if(!s || s.indexOf(":") !== -1 || s.indexOf(String.fromCharCode(92)) !== -1) return false;
  return s.split("/").every(d => d && d !== "." && d !== "..");
}

/* Mediefiler, der ligger ved siden af filen, og som filen selv opgiver (standardens 5.7). */
function zipMedier(data){
  if(!data || !Array.isArray(data.media)) return [];
  return data.media.map(m => m && m.fil).filter(zipSikkerSti);
}

async function zipFraMappe(handle, sti){
  const dele = sti.split("/");
  try{
    let h = handle;
    for(let i = 0; i < dele.length - 1; i++) h = await h.getDirectoryHandle(dele[i]);
    const fh = await h.getFileHandle(dele[dele.length - 1]);
    return await fh.getFile();
  }catch(e){ return null; }
}

/* Filer valgt med browserens filfelt: stien regnes fra den valgte mappe. */
function zipKort(filer){
  const kort = new Map();
  filer.forEach(f => {
    const dele = (f.webkitRelativePath || f.name).split("/");
    if(dele.length > 1) dele.shift();
    kort.set(dele.join("/"), f);
  });
  return kort;
}

async function zipFind(liste, find){
  const filer = [], mangler = [], sete = new Set();
  for(const p of liste){
    for(const sti of [p.filnavn].concat(p.medier || [])){
      if(!zipSikkerSti(sti) || sete.has(sti)) continue;
      sete.add(sti);
      const file = await find(sti);
      if(file) filer.push({sti: sti, file: file}); else mangler.push(sti);
    }
  }
  return {filer: filer, mangler: mangler};
}

function zipMappenavn(titel){
  const s = String(titel || "").toLowerCase()
    .replace(/æ/g, "ae").replace(/ø/g, "oe").replace(/å/g, "aa")
    .replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").slice(0, 60);
  return s || "soegeside";
}

/* Laver selve zip-filen. Filerne bliver ikke lagt i hukommelsen, kun henvist til. */
async function zipLav(filer, mappe, vis){
  const tekst = new TextEncoder(), dele = [], central = [];
  const nu = new Date();
  const tid = (nu.getHours() << 11) | (nu.getMinutes() << 5) | (nu.getSeconds() >> 1);
  const dato = ((nu.getFullYear() - 1980) << 9) | ((nu.getMonth() + 1) << 5) | nu.getDate();
  let pos = 0, centralStr = 0;

  for(let i = 0; i < filer.length; i++){
    const f = filer[i];
    vis("Pakker fil " + (i + 1) + " af " + filer.length + ": " + f.sti);
    const navn = tekst.encode(mappe + "/" + f.sti);
    const crc = await zipKontrol(f.file);
    const str = f.file.size;

    const h = new Uint8Array(30 + navn.length), v = new DataView(h.buffer);
    v.setUint32(0, 0x04034b50, true); v.setUint16(4, 20, true); v.setUint16(6, 0x0800, true);
    v.setUint16(8, 0, true); v.setUint16(10, tid, true); v.setUint16(12, dato, true);
    v.setUint32(14, crc, true); v.setUint32(18, str, true); v.setUint32(22, str, true);
    v.setUint16(26, navn.length, true); v.setUint16(28, 0, true);
    h.set(navn, 30);

    const c = new Uint8Array(46 + navn.length), w = new DataView(c.buffer);
    w.setUint32(0, 0x02014b50, true); w.setUint16(4, 20, true); w.setUint16(6, 20, true);
    w.setUint16(8, 0x0800, true); w.setUint16(10, 0, true); w.setUint16(12, tid, true);
    w.setUint16(14, dato, true); w.setUint32(16, crc, true); w.setUint32(20, str, true);
    w.setUint32(24, str, true); w.setUint16(28, navn.length, true); w.setUint32(42, pos, true);
    c.set(navn, 46);

    dele.push(h, f.file);
    central.push(c);
    pos += h.length + str;
    centralStr += c.length;
  }

  const e = new Uint8Array(22), x = new DataView(e.buffer);
  x.setUint32(0, 0x06054b50, true); x.setUint16(8, filer.length, true); x.setUint16(10, filer.length, true);
  x.setUint32(12, centralStr, true); x.setUint32(16, pos, true);
  return new Blob(dele.concat(central, [e]), {type: "application/zip"});
}

/* Giver besked og henter zip-filen ned. Formatet kan højst rumme ca. 4 GB. */
async function zipHent(fund, titel, vis){
  let i_alt = 0;
  fund.filer.forEach(f => { i_alt += f.file.size + 200; });
  if(i_alt >= 0xFFFFFFFF || fund.filer.length >= 65000){
    vis("Der er for meget til én zip-fil. Den kan højst rumme ca. 4 GB. Kopiér i stedet hele mappen, fx til et USB-stik.");
    return;
  }
  const mappe = zipMappenavn(titel);
  const blob = await zipLav(fund.filer, mappe, vis);
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url; a.download = mappe + ".zip";
  document.body.appendChild(a); a.click(); a.remove();
  setTimeout(() => URL.revokeObjectURL(url), 120000);
  let besked = mappe + ".zip er hentet ned med " + fund.filer.length + " filer. Pak den ud, og åbn søgesiden.";
  if(fund.mangler.length){
    besked += " Disse filer kunne ikke findes og er ikke med: " + fund.mangler.join(", ") + ".";
  }
  vis(besked);
}
"""

# Kun i den færdige søgeside: Her skal man pege på mappen, fordi en side, der er
# åbnet fra en mappe, ikke selv må læse de andre filer. Online er knappen skjult.
SOEGESIDE_ZIP_SIDEN = SOEGESIDE_ZIP_KODE + """
(function(){
  const boks = document.getElementById("zipboks");
  const knap = document.getElementById("zipknap");
  const status = document.getElementById("zipstatus");
  const felt = document.createElement("input");
  felt.type = "file";
  felt.webkitdirectory = true;
  felt.hidden = true;
  document.body.appendChild(felt);

  const online = location.protocol === "http:" || location.protocol === "https:";
  if(online || (!window.showDirectoryPicker && !("webkitdirectory" in felt))){
    boks.hidden = true;
    return;
  }
  const vis = t => { status.textContent = t; };

  async function pak(find){
    knap.disabled = true;
    try{
      const egen = await find(MIG);
      const fund = await zipFind(DATA, find);
      if(!egen && !fund.filer.length){
        vis("Der er ingen af samlingens filer i den mappe. Vælg den mappe, hvor søgesiden ligger.");
        return;
      }
      if(egen) fund.filer.unshift({sti: MIG, file: egen});
      else fund.mangler.unshift(MIG);
      await zipHent(fund, document.title, vis);
    }catch(e){
      vis("Zip-filen kunne ikke laves. Prøv igen, eller kopiér i stedet hele mappen.");
    }finally{
      knap.disabled = false;
    }
  }

  knap.addEventListener("click", async () => {
    if(location.protocol === "about:"){
      vis("Her i forhåndsvisningen virker knappen ikke. Den virker i den færdige søgeside.");
      return;
    }
    if(window.showDirectoryPicker){
      try{
        const h = await window.showDirectoryPicker({mode: "read"});
        await pak(sti => zipFraMappe(h, sti));
        return;
      }catch(e){
        if(e && e.name === "AbortError") return;
      }
    }
    felt.click();
  });

  felt.addEventListener("change", async () => {
    const kort = zipKort(Array.from(felt.files));
    await pak(async sti => kort.get(sti) || null);
    felt.value = "";
  });
})();
"""

SOEGESIDE_ZIP_HTML = """<section class="zipboks" id="zipboks" aria-labelledby="ziptitel">
<h2 id="ziptitel">Gem hele samlingen</h2>
<p>Saml søgesiden og alle filerne i én zip-fil, fx til et USB-stik eller en anden computer. Du bliver bedt om at vælge den mappe, hvor søgesiden ligger. Der sendes ikke noget nogen steder hen.</p>
<button type="button" id="zipknap">Gem det hele som zip-fil</button>
<p class="zipstatus" id="zipstatus" role="status" aria-live="polite"></p>
</section>

"""

SOEGESIDE_ZIP_CSS_SIDEN = """.zipboks{max-width:1100px;margin:0 auto 2rem;padding:0 1.25rem;font-size:1.05rem}
.zipboks[hidden]{display:none}
.zipboks h2{font-size:1.25rem;margin:0 0 .3rem}
.zipboks p{margin:0 0 .7rem;max-width:70ch;color:var(--muted)}
.zipboks button{font-family:inherit;font-size:1.05rem;min-height:48px;padding:.5rem 1.25rem;cursor:pointer;
  background:var(--flade);color:var(--accent);border:2px solid var(--accent);border-radius:999px}
.zipboks button:hover{background:var(--accent);color:#fff}
.zipboks button[disabled]{opacity:.6;cursor:wait}
.zipboks .zipstatus{margin:.7rem 0 0;color:var(--ink);word-break:break-word}
.zipboks .zipstatus:empty{display:none}
@media print{.zipboks{display:none}}
"""

SOEGESIDE_ZIP_CSS_BYGGER = """button.rolig[disabled]{border-color:var(--line);color:var(--muted);cursor:not-allowed}
"""

SOEGESIDE_ZIP_BYGGER = """/* --- Gem det hele som zip-fil (fri udgave) --- */
let zipValgt = null;   // sat, når mappen er valgt med browserens filfelt
document.getElementById("gemZip").addEventListener("click", async () => {
  const knap = document.getElementById("gemZip");
  const titel = document.getElementById("titel").value.trim() || "Søg i samlingen";
  const kort = zipValgt || zipKort(valgteFiler.map(f => f.file));
  const find = mappeHandle ? (sti => zipFraMappe(mappeHandle, sti)) : (async sti => kort.get(sti) || null);
  knap.disabled = true;
  try{
    const fund = await zipFind(poster, find);
    fund.filer.unshift({sti: OUTPUT_NAME, file: new Blob([byg()], {type: "text/html"})});
    await zipHent(fund, titel, t => saetStatus(escapeHtml(t)));
  }catch(e){
    saetStatus('<span class="fejl">Zip-filen kunne ikke laves. Prøv igen, eller kopiér i stedet hele mappen.</span>');
  }finally{
    knap.disabled = false;
  }
});

"""


def soegeside_zip(s, fil):
    """Lægger zip-knappen ind: i byggeren uden ekstra trin, i søgesiden med valg af mappe."""
    # Den færdige søgeside
    s = ret(s, '<footer>\nDenne side er selvstændig', SOEGESIDE_ZIP_HTML + '<footer>\nDenne side er selvstændig', fil)
    s = ret(s, '<\\/script>\n</body>', SOEGESIDE_ZIP_SIDEN + '<\\/script>\n</body>', fil)
    # Byggeren
    s = ret(s, 'appformat: (data && (data.format || data.builder)) || ""};',
            'appformat: (data && (data.format || data.builder)) || "",\n'
            '                medier: zipMedier(data)};', fil)
    s = ret(s, '  poster = [];\n  sprunget = [];\n\n  filer.sort',
            '  poster = [];\n  sprunget = [];\n  valgteFiler = filer;\n\n  filer.sort', fil)
    s = ret(s, '<p class="hint" id="gemHint">Vælg først en mappe.</p>',
            '<p class="hint" id="gemHint">Vælg først en mappe.</p>\n'
            '      <button type="button" class="rolig" id="gemZip" disabled>Gem det hele som zip-fil</button>\n'
            '      <p class="hint">Søgesiden og filerne samlet i én fil, fx til et USB-stik eller en anden computer.</p>', fil)
    s = ret(s, 'document.getElementById("gem").disabled = true;',
            'document.getElementById("gem").disabled = true;\n    document.getElementById("gemZip").disabled = true;', fil)
    s = ret(s, 'document.getElementById("gem").disabled = false;',
            'document.getElementById("gem").disabled = false;\n  document.getElementById("gemZip").disabled = false;', fil)
    s = ret(s, '  const alle = Array.from(mappeInput.files);',
            '  const alle = Array.from(mappeInput.files);\n  zipValgt = zipKort(alle);', fil)
    s = ret(s, '  document.getElementById("mappeHint").textContent = "Valgte filer: "',
            '  zipValgt = null;\n  document.getElementById("mappeHint").textContent = "Valgte filer: "', fil)
    s = ret(s, '  const items = Array.from(e.dataTransfer.items || []);',
            '  zipValgt = null;\n  const items = Array.from(e.dataTransfer.items || []);', fil)
    s = ret(s, '/* --- Startbesked --- */',
            SOEGESIDE_ZIP_KODE.lstrip('\n') + '\n' + SOEGESIDE_ZIP_BYGGER + '/* --- Startbesked --- */', fil)
    return s


def soegeside_design(s, fil):
    """Lægger det frie design på: CSS i både byggeren og skabelonen, temavælger og tema i den færdige side."""
    dele = s.split('</style>')
    if len(dele) != 3:
        sys.exit(f'{fil}: forventede to <style>-blokke, fandt {len(dele) - 1}')
    s = (dele[0] + SOEGESIDE_BYGGER + SOEGESIDE_ZIP_CSS_BYGGER + '</style>'
         + dele[1] + SOEGESIDE_SIDEN + SOEGESIDE_ZIP_CSS_SIDEN + '</style>' + dele[2])
    if s.count('<html lang="da">') != 2:
        sys.exit(f'{fil}: forventede to <html lang="da">')
    s = s.replace('<html lang="da">', '<html lang="da" data-tema="vinter">', 1)
    s = s.replace('<html lang="da">', '<html lang="da" data-tema="__TEMA__">', 1)
    s = ret(s, '<h1>Søgeside · Bygger</h1>', '<h1>Søgeside · Bygger <span class="fri">fri udgave</span></h1>', fil)
    s = ret(s, '    <div class="trin">\n      <h3>3. Gem søgesiden</h3>',
            soegeside_temavaelger() + '    <div class="trin">\n      <h3>4. Gem søgesiden</h3>', fil)
    s = ret(s, '  return SKABELON\n    .split("__MARKER__")',
            '  return SKABELON\n    .split("__TEMA__").join(valgtTema())\n    .split("__MARKER__")', fil)
    s = ret(s, '/* --- Startbesked --- */', SOEGESIDE_TEMA_KODE + '/* --- Startbesked --- */', fil)
    return s


def lav_soegeside():
    fil = 'tools/soegeside-bygger.html'
    s = (ROD / fil).read_text(encoding='utf-8')
    s = soegeside_design(s, fil)
    s = soegeside_zip(s, fil)
    s = erstat(s, r'"Søg i lokalhistorier"', '"Søg i samlingen"', antal=3, navn=fil)
    s = erstat(s, r'aria-label="Filtrér på område"><option value="">Alle områder</option>',
               'aria-label="Filtrér på udgiver"><option value="">Alle udgivere</option>', navn=fil)
    s = erstat(s, r'<option value="omraade">Område</option>', '<option value="omraade">Udgiver</option>', navn=fil)
    s = erstat(s, r'"Uden område \("', '"Uden udgiver ("', navn=fil)
    # Kun byggerens egen titel og version, ikke titlen i den søgeside, den laver.
    s, n = re.subn(r'^<title>([^<]*)</title>', r'<title>\1 · fri udgave</title>', s, count=1, flags=re.M)
    s = erstat(s, r'(Søgeside · Bygger — version [0-9.]+)', r'\1 · fri udgave med eget design', navn=fil)
    (UD / 'soegeside-bygger.html').write_text(s, encoding='utf-8')
    print('Lavet: fri/soegeside-bygger.html')


def ret(s, gammel, ny, fil):
    """Som erstat, men for almindelig tekst uden mønstre. Teksten skal stå der én gang."""
    if s.count(gammel) != 1:
        sys.exit(f'{fil}: forventede én gang {gammel[:60]!r}, fandt {s.count(gammel)}')
    return s.replace(gammel, ny)


def lav_emneord():
    """Forslag til emneord: foreningernes fælles emneord, men som frie forslag.
    Roskilde Festival og andre ting, der kun gælder foreningerne, er taget ud."""
    fil = 'tools/emneord.html'
    s = (ROD / fil).read_text(encoding='utf-8')
    s = erstat(s, r'<!-- Fælles emneord · version ([0-9.]+)\.[^>]*-->',
               r'<!-- Forslag til emneord · version \1 · fri udgave. Laves af fri/lav-fri-udgave.py ud fra tools/emneord.html. Ret ikke i hånden. -->',
               navn=fil)
    s = ret(s, '<title>Fælles emneord</title>', '<title>Forslag til emneord · fri udgave</title>', fil)
    s = ret(s, '<h1>Fælles emneord</h1>', '<h1>Forslag til emneord</h1>', fil)
    s = erstat(s, r'<span>Version ([0-9.]+) · [^<]*</span>',
               r'<span>Version \1 · fri udgave · gode ord til feltet Emne / nøgleord</span>', navn=fil)
    s = erstat(s, r'<div class="intro">.*?\n  </div>\n\n  <div class="soeg"', '''<div class="intro">
    <div>
      <h2>Hvad er emneord?</h2>
      <p>Emneordene fortæller, <em>hvad</em> en historie handler om. De gør det lettere at finde den igen, fx på en søgeside.</p>
    </div>
    <div>
      <h2>Forslag, ikke regler</h2>
      <p>Her er gode ord, som mange bruger. Bruger I de samme, er det let at finde alt om fx <strong>Musik</strong> på én gang. Passer ingen af dem, så skriv jeres eget ord.</p>
    </div>
    <div>
      <h2>Sådan skrives de</h2>
      <p>I feltet <strong>Emne / nøgleord</strong>: først et eller flere ord herfra, så jeres egne præcise ord. Adskil dem med semikolon.</p>
      <p><span class="eksempel"><strong>Musik</strong>; <strong>Skole og børn</strong>; Skolekoret; 2026</span></p>
    </div>
  </div>

  <div class="soeg"''', navn=fil)
    s = ret(s, '  ["Roskilde Festival", "selve festivalen, camps og festivalens kulturliv", "festival, Dream City"],\n', '', fil)
    s = ret(s, '"RUC, forskning"]', '"forskning"]', fil)
    s = ret(s, 'aria-label="De fælles emneord"', 'aria-label="Forslag til emneord"', fil)
    s = ret(s, '<strong>Undgå:</strong>', '<strong>Brug ordet i stedet for:</strong>', fil)
    s = ret(s, 'EMNEORD.length + " fælles emneord"', 'EMNEORD.length + " forslag til emneord"', fil)
    s = ret(s, '"Intet fælles emneord passer. Brug jeres eget ord efter de fælles, eller foreslå et nyt."',
            '"Ingen af forslagene passer. Skriv bare jeres eget ord."', fil)
    s = erstat(s, r'\n      <li><strong>Roskilde Festival</strong>.*?</li>', '', navn=fil)
    s = ret(s, 'En historie om Domkirken kan få alle tre.', 'En historie om en gammel kirke kan få alle tre.', fil)
    s = erstat(s, r'\n    <p><strong>Gader og veje</strong> er også en fast serie[^\n]*</p>', '', navn=fil)
    s = ret(s, 'Sådan giver du en færdig historie de fælles emneord', 'Sådan giver du en færdig historie emneord', fil)
    s = ret(s, 'Find de emneord, der passer, her på siden.', 'Find de ord, der passer, her på siden.', fil)
    s = ret(s, 'efter udgiveren. Lad jeres egne', 'først. Lad jeres egne', fil)
    s = erstat(s, r'<li>Gem filen igen, og læg den op i søgeindekset.*?</li>', '<li>Gem filen igen.</li>', navn=fil)
    s = ret(s, '<p>Mangler der et emneord, så aftal det med de andre foreninger. Det skrives på listen, før det tages i brug.</p>',
            '<p>Passer ingen af forslagene, så skriv jeres eget ord. Det er helt i orden.</p>', fil)
    for ord in ('andre foreninger', 'alle foreninger', 'hvilken forening', 'Roskilde', 'søgeindeks', 'Undgå'):
        if ord in s:
            sys.exit(f'{fil}: «{ord}» står stadig i den frie udgave. Ret opskriften.')
    (UD / 'emneord.html').write_text(s, encoding='utf-8')
    print('Lavet: fri/emneord.html')


# Værktøjer, der allerede er generelle, og som bare kopieres med.
KOPIER = ['tools/feltoptager.html', 'tools/lydlab.html', 'tools/fotolab.html', 'tools/skyggeramme.html', 'tools/maa-det-komme-med.html', 'tools/qr-kode.html']


ZIP_NAVN = 'lokalhistorier-fri-udgave.zip'
ZIP_MAPPE = 'lokalhistorier-fri-udgave'


def lav_zip():
    """Pakker forsiden, byggerne, værktøjerne og vejledningen i én zip-fil, som
    kan hentes fra forsiden. Opskriften selv kommer ikke med. Alle filer får samme
    faste tidspunkt, så zip-filen kun ændrer sig, når indholdet gør."""
    filer = sorted(p for p in UD.iterdir()
                   if p.suffix in ('.html', '.md') and p.name != ZIP_NAVN)
    with zipfile.ZipFile(UD / ZIP_NAVN, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for p in filer:
            info = zipfile.ZipInfo(f'{ZIP_MAPPE}/{p.name}', date_time=(2026, 1, 1, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o644 << 16
            z.writestr(info, p.read_bytes())
    print(f'Lavet: fri/{ZIP_NAVN} ({len(filer)} filer, {(UD / ZIP_NAVN).stat().st_size // 1024} KB)')


if __name__ == '__main__':
    UD.mkdir(exist_ok=True)
    for f in BYGGERE:
        lav(f)
    lav_soegeside()
    lav_emneord()
    for f in KOPIER:
        (UD / Path(f).name).write_bytes((ROD / f).read_bytes())
        print(f'Kopieret: fri/{Path(f).name}')
    lav_zip()
