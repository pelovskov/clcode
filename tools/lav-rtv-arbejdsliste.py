#!/usr/bin/env python3
"""
Opskrift: laver Roskilde TV's arbejdsliste ud fra det offentlige videoarkiv.

roskilde-tv-videoarkiv.html (i repoets rod) er den offentlige udgave: søg, og se
udsendelsen på YouTube. Arbejdslisten er den samme side med en knap
"Lav mediekort" på hver udsendelse. Knappen åbner Mediekort · Bygger med
videoen og titlen udfyldt. Arbejdslisten er til Roskilde TV og dem, der laver
mediekort, og linkes kun fra dashboardet – aldrig fra portalen.

Opskriften laver også sbtv/rtv-skabelon.html: arbejdslisten med fælles
prikker (rød, gul, grøn), som vises af sbtv/rtv.php på webhotellet.

Kør opskriften igen, når videoarkivet er opdateret:

    python3 tools/lav-rtv-arbejdsliste.py

Ret aldrig i tools/rtv-arbejdsliste.html i hånden.
"""
import sys
from pathlib import Path

ROD = Path(__file__).resolve().parent.parent
KILDE = ROD / 'roskilde-tv-videoarkiv.html'
UD = ROD / 'tools' / 'rtv-arbejdsliste.html'


def erstat(s, gammel, ny):
    if s.count(gammel) != 1:
        sys.exit(f'Videoarkivet passer ikke længere til opskriften. Fandt ikke: {gammel[:70]!r}')
    return s.replace(gammel, ny)


def main():
    s = KILDE.read_text(encoding='utf-8')
    s = erstat(s, '<title>Roskilde TV – Videoarkiv</title>',
               '<title>Roskilde TV – arbejdsliste til mediekort</title>')
    s = erstat(s, '<h1>Roskilde TV – Videoarkiv</h1>',
               '<h1>Roskilde TV – arbejdsliste til mediekort</h1>')
    s = erstat(s, '" videoer fra kanalen – søg, filtrer og klik for at se på YouTube."',
               '" videoer fra kanalen. Find en udsendelse, og tryk «Lav mediekort» for at åbne Mediekort · Bygger med videoen udfyldt."')
    # Knappen ligger under linket til YouTube, ikke inde i det.
    s = erstat(s, """'<div class="b"><div class="t">'+hl(v.t,r.terms)+'</div><div class="k">'+esc(v.c)+'</div></div></a></article>'""",
               """'<div class="b"><div class="t">'+hl(v.t,r.terms)+'</div><div class="k">'+esc(v.c)+'</div></div></a>'+"""
               """'<a class="lav" target="_blank" rel="noopener" href="../mediekort-bygger.html?yt='+v.id+'&amp;titel='+encodeURIComponent(v.t)+'">Lav mediekort</a></article>'""")
    s = erstat(s, '</style>',
               '.lav{display:block;flex:0 0 auto;margin:0 14px 14px;padding:9px 14px;border:2px solid var(--acc);border-radius:999px;'
               'text-align:center;font-weight:bold;color:var(--acc2);text-decoration:none}\n'
               '.lav:hover{background:var(--acc);color:#fff}\n'
               '.list .lav{display:inline-block;margin:0 14px 10px}\n'
               '</style>')
    s = erstat(s, '<html lang="da">',
               '<html lang="da">\n<!-- Arbejdsliste lavet af tools/lav-rtv-arbejdsliste.py ud fra roskilde-tv-videoarkiv.html. Ret ikke i hånden. -->')
    UD.write_text(s, encoding='utf-8')
    print(f'Lavet: {UD.relative_to(ROD)}')


# ---------------------------------------------------------------------------
# Arbejdslisten med fælles prikker til webhotellet (sbtv/rtv.php).
# Skabelonen sbtv/rtv-skabelon.html vises af rtv.php, som kræver login og
# gemmer prikkerne. Grøn sættes også af sig selv, når der ligger et mediekort
# til videoen i søgeindekset.
# ---------------------------------------------------------------------------
SKABELON = ROD / 'sbtv' / 'rtv-skabelon.html'

PRIK_CSS = '''.card{position:relative}
.dot{position:absolute;top:8px;left:8px;z-index:2;width:40px;height:40px;border-radius:50%;border:3px solid #fff;
background:#8f8a80;box-shadow:0 1px 6px rgba(0,0,0,.45);cursor:pointer;padding:0}
.dot[data-s=roed]{background:#c62828}.dot[data-s=gul]{background:#f2b705}.dot[data-s=groen]{background:#2e7d32}
.dot:hover{transform:scale(1.08)}
.st{margin:0 14px 10px;font-size:.85em;color:var(--mute)}
.lav{display:block;flex:0 0 auto;margin:0 14px 14px;padding:9px 14px;border:2px solid var(--acc);border-radius:999px;text-align:center;font-weight:bold;color:var(--acc2);text-decoration:none}
.lav:hover{background:var(--acc);color:#fff}
.list .lav{display:inline-block;margin:0 14px 10px}
.stchips{max-width:1300px;margin:0 auto;padding:10px 20px 0;display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.stchips .chip i{display:inline-block;width:12px;height:12px;border-radius:50%;margin-right:6px;vertical-align:-1px}
.stchips .chip i.roed{background:#c62828}.stchips .chip i.gul{background:#f2b705}.stchips .chip i.groen{background:#2e7d32}
.hjaelp{max-width:1300px;margin:0 auto;padding:6px 20px 0;color:var(--mute);font-size:.88em}
.hjaelp kbd{font:inherit;border:1px solid var(--line);border-radius:4px;padding:0 5px;background:var(--card)}
'''

PRIK_JS = r'''
/* ---- Fælles prikker ----
   Rød: bør have et mediekort. Gul: mediekortet er ved at blive lavet.
   Grøn: færdig og lagt i søgeindekset – sættes også af sig selv, når der
   ligger et mediekort til videoen i søgeindekset. Prikkerne gemmes af
   rtv.php på webhotellet og ses af alle, der er logget ind. */
const RTV=window.RTV||null, MK=(RTV&&RTV.mediekort)||"../mediekort-bygger.html";
let ST={}, AUTO={}, sf="", hovId="";
const SNAVN={"":"ingen status",roed:"bør have et mediekort",gul:"mediekortet er ved at blive lavet",groen:"færdig og lagt i søgeindekset"};
const SFARVE={roed:"Rød",gul:"Gul",groen:"Grøn"};
function stOf(id){return AUTO[id]?{s:"groen",auto:true}:(ST[id]||{s:""})}
function dotHtml(v){const o=stOf(v.id);return '<button type="button" class="dot" data-id="'+v.id+'" data-s="'+o.s+'" aria-label="Status: '+SNAVN[o.s]+'. Tryk for at skifte." title="'+SNAVN[o.s]+'"></button>'}
function datoKort(t){const d=new Date(t*1000);return d.getDate()+". "+["jan.","feb.","mar.","apr.","maj","jun.","jul.","aug.","sep.","okt.","nov.","dec."][d.getMonth()]}
function stLine(v){const o=stOf(v.id);if(!o.s)return"";return '<div class="st">'+SFARVE[o.s]+(o.auto?" · ligger i søgeindekset":(o.af?" · "+esc(o.af)+(o.tid?" · "+datoKort(o.tid):""):""))+'</div>'}
function say(t){$("info").textContent=t}
async function hentPrikker(){
  if(!RTV)return;
  try{const r=await fetch(RTV.api+"?status",{credentials:"same-origin",cache:"no-store"});if(!r.ok)return;
    const d=await r.json();ST=d.status||{};AUTO={};(d.auto||[]).forEach(id=>AUTO[id]=1);render()}catch(e){}
}
async function saet(id,s){
  if(!RTV){alert("Prikkerne kan kun sættes i arbejdslisten på webhotellet, når du er logget ind.");return}
  if(AUTO[id]){say("Der ligger et mediekort til videoen i søgeindekset, så den er altid grøn.");return}
  const fokus=document.activeElement&&document.activeElement.classList.contains("dot");
  const fd=new FormData();fd.append("csrf",RTV.csrf);fd.append("id",id);fd.append("s",s);
  try{const r=await fetch(RTV.api,{method:"POST",body:fd,credentials:"same-origin"});const d=await r.json();
    if(!d.ok){alert(d.fejl||"Prikken kunne ikke gemmes.");return}
    if(d.post)ST[id]=d.post;else delete ST[id];render();
    if(fokus){const b=document.querySelector('.dot[data-id="'+id+'"]');if(b)b.focus()}
  }catch(e){alert("Prikken kunne ikke gemmes. Er du stadig logget ind?")}
}
const RAEKKE=["","roed","gul","groen"];
document.addEventListener("click",e=>{const b=e.target.closest&&e.target.closest(".dot");if(!b)return;
  e.preventDefault();const cur=stOf(b.dataset.id).s;saet(b.dataset.id,RAEKKE[(RAEKKE.indexOf(cur)+1)%4])});
// Tasterne gælder den video, musen er over – eller, når man bruger Tab, den video, der er valgt.
let medMus=true;
document.addEventListener("mouseover",e=>{const c=e.target.closest&&e.target.closest(".card");hovId=c?c.dataset.id:"";medMus=true});
document.addEventListener("keydown",e=>{
  if(e.key==="Tab"){medMus=false;return}
  const tag=(e.target&&e.target.tagName)||"";if(tag==="INPUT"||tag==="TEXTAREA"||tag==="SELECT"||e.ctrlKey||e.metaKey||e.altKey)return;
  const k=e.key.toLowerCase(),s=k==="r"?"roed":(k==="y"||k==="o")?"gul":k==="g"?"groen":k==="0"?"":null;if(s===null)return;
  const c=document.activeElement&&document.activeElement.closest?document.activeElement.closest(".card"):null;
  const id=medMus?(hovId||(c&&c.dataset.id)):((c&&c.dataset.id)||hovId);if(!id)return;e.preventDefault();saet(id,s)});
function stChips(){
  const n={ingen:0,roed:0,gul:0,groen:0};V.forEach(v=>{n[stOf(v.id).s||"ingen"]++});
  const el=$("stchips");el.innerHTML='<span>Status:</span>';
  [["","Alle",V.length],["roed","Rød – mangler",n.roed],["gul","Gul – i gang",n.gul],["groen","Grøn – færdig",n.groen],["ingen","Uden prik",n.ingen]].forEach(([k,t,c])=>{
    const b=document.createElement("button");b.className="chip";b.setAttribute("aria-pressed",sf===k);
    b.innerHTML=(k&&k!=="ingen"?'<i class="'+k+'"></i>':"")+t+"<small>"+c+"</small>";
    b.onclick=()=>{sf=k;shown=PAGE;render()};el.appendChild(b)});
}
const _render=render;render=function(){_render();stChips()};
if(RTV){hentPrikker();setInterval(hentPrikker,60000)}
'''

KORT_START = '\'<article class="card"><a href="https://youtu.be/\'+v.id+\'" target="_blank" rel="noopener">\'+'
KORT_START_NY = '\'<article class="card" data-id="\'+v.id+\'">\'+dotHtml(v)+\'<a href="https://youtu.be/\'+v.id+\'" target="_blank" rel="noopener">\'+'
KORT_SLUT = '\'<div class="b"><div class="t">\'+hl(v.t,r.terms)+\'</div><div class="k">\'+esc(v.c)+\'</div></div></a></article>\''
KORT_SLUT_NY = ('\'<div class="b"><div class="t">\'+hl(v.t,r.terms)+\'</div><div class="k">\'+esc(v.c)+\'</div></div></a>\'+stLine(v)+'
                '\'<a class="lav" target="_blank" rel="noopener" href="\'+MK+\'?yt=\'+v.id+\'&amp;titel=\'+encodeURIComponent(v.t)+\'">Lav mediekort</a></article>\'')


def lav_med_prikker():
    s = KILDE.read_text(encoding='utf-8')
    s = erstat(s, '<title>Roskilde TV – Videoarkiv</title>', '<title>Roskilde TV – arbejdsliste</title>')
    s = erstat(s, '<h1>Roskilde TV – Videoarkiv</h1>', '<h1>Roskilde TV – arbejdsliste</h1>')
    s = erstat(s, '" videoer fra kanalen – søg, filtrer og klik for at se på YouTube."',
               '" videoer fra kanalen. Prikkerne viser, hvor langt vi er med mediekortene."')
    s = erstat(s, '</style>', PRIK_CSS + '</style>')
    s = erstat(s, '<div class="chips" id="chips" role="group" aria-label="Kategorier"></div>',
               '<div class="chips" id="chips" role="group" aria-label="Kategorier"></div>\n'
               '<div class="stchips" id="stchips" role="group" aria-label="Status"></div>\n'
               '<p class="hjaelp">Tryk på prikken for at skifte farve, eller hold musen over en video og tryk '
               '<kbd>R</kbd> rød (bør laves), <kbd>Y</kbd> gul (i gang), <kbd>G</kbd> grøn (færdig) eller <kbd>0</kbd> for ingen prik.</p>')
    s = erstat(s, KORT_START, KORT_START_NY)
    s = erstat(s, KORT_SLUT, KORT_SLUT_NY)
    s = erstat(s, '    let ok=true,score=0;', '    if(sf&&(stOf(v.id).s||"ingen")!==sf)continue;\n    let ok=true,score=0;')
    s = erstat(s, 'chips();render();\n</script>', PRIK_JS + 'chips();render();\n</script>')
    s = erstat(s, '<html lang="da">',
               '<html lang="da">\n<!-- Skabelon til sbtv/rtv.php, lavet af tools/lav-rtv-arbejdsliste.py ud fra roskilde-tv-videoarkiv.html. Ret ikke i hånden. -->')
    SKABELON.write_text(s, encoding='utf-8')
    print(f'Lavet: {SKABELON.relative_to(ROD)}')


if __name__ == '__main__':
    main()
    lav_med_prikker()
