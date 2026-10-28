#!/usr/bin/env python3
"""
Opskrift: laver Roskilde TV's arbejdsliste ud fra det offentlige videoarkiv.

roskilde-tv-videoarkiv.html (i repoets rod) er den offentlige udgave: søg, og se
udsendelsen på YouTube. Arbejdslisten er den samme side med en knap
"Lav mediekort" på hver udsendelse. Knappen åbner Mediekort · Bygger med
videoen og titlen udfyldt. Arbejdslisten er til Roskilde TV og dem, der laver
mediekort, og linkes kun fra dashboardet – aldrig fra portalen.

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
               '.lav{display:block;margin:0 14px 14px;padding:9px 14px;border:2px solid var(--acc);border-radius:999px;'
               'text-align:center;font-weight:bold;color:var(--acc2);text-decoration:none}\n'
               '.lav:hover{background:var(--acc);color:#fff}\n'
               '.list .lav{display:inline-block;margin:0 14px 10px}\n'
               '</style>')
    s = erstat(s, '<html lang="da">',
               '<html lang="da">\n<!-- Arbejdsliste lavet af tools/lav-rtv-arbejdsliste.py ud fra roskilde-tv-videoarkiv.html. Ret ikke i hånden. -->')
    UD.write_text(s, encoding='utf-8')
    print(f'Lavet: {UD.relative_to(ROD)}')


if __name__ == '__main__':
    main()
