#!/usr/bin/env python3
"""
Opskrift: laver "Lokalhistorier · fri udgave" ud fra de almindelige byggere.

Den frie udgave er til enkeltpersoner, skoler og institutioner, som ikke er
med i den fælles portal. Forskellene fra de almindelige byggere:

- Udgiveren skrives i et almindeligt tekstfelt i stedet for at vælges på en liste.
- Kun to farvetemaer: Vinter og Sommer.
- Udgiveren sættes ikke ind som første emneord, og identifikatoren har
  intet foreningspræfiks, fx "side-sommerlejren-2026".
- Foreningernes særlige ting er fjernet, fx Sidebyggerens faste serier.
- Filformatet er det samme, så filerne kan åbnes i begge udgaver.

Kør opskriften igen, når en almindelig bygger er blevet forbedret:

    python3 fri/lav-fri-udgave.py

Den læser byggerne i repoets rod og skriver den frie udgave i mappen fri/.
Ret aldrig i filerne i fri/ i hånden – ret i opskriften eller i den
almindelige bygger, og kør opskriften igen.
"""
import re
import sys
from pathlib import Path

ROD = Path(__file__).resolve().parent.parent
UD = ROD / 'fri'

# De byggere, der laves i fri udgave. Flere kommer til trin for trin.
BYGGERE = ['side-bygger.html']

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
  }
};"""

VAELGER = [
    ('vinter', 'Vinter', '#2F5D8A', ['#2F5D8A', '#BFD7EA', '#EEF2F6']),
    ('sommer', 'Sommer', '#2F7A46', ['#2F7A46', '#F4C542', '#FBF7EA']),
]

UDGIVER_FELT = ('<input type="text" id="dcPublisher" '
                'placeholder="fx 7.b, Roskilde Lille Skole eller dit eget navn">')
UDGIVER_HINT = 'dc.publisher — skriv, hvem der står bag, fx en klasse, en skole eller dit navn'


def erstat(s, moenster, ny, antal=1, navn='', flags=re.S):
    s2, n = re.subn(moenster, ny, s, flags=flags)
    if antal is not None and n != antal:
        sys.exit(f'{navn}: forventede {antal} træf for {moenster!r}, fandt {n}')
    return s2


def lav(fil):
    s = (ROD / fil).read_text(encoding='utf-8')

    # --- Udgiver: tekstfelt i stedet for liste ---
    s = erstat(s, r'<select id="dcPublisher">.*?</select>', UDGIVER_FELT, navn=fil)
    s = erstat(s, r'dc\.publisher — vælg område, så filen kan filtreres i søgeindekset', UDGIVER_HINT, navn=fil)
    s = erstat(s, r'const PUBLISHERS = \[.*?\n\];',
               '// Fri udgave: ingen fast liste over udgivere. Udgiveren skrives i et tekstfelt.\nconst PUBLISHERS = [];',
               navn=fil)
    s = erstat(s, r'function ensurePublisherOption\(v\)\{.*?\n\}', 'function ensurePublisherOption(v){}', navn=fil)
    # Udgiveren sættes ikke ind som første emneord.
    s = erstat(s, r'if \(dc\.publisher(?: \|\| series)?\)\{', 'if (false){  // fri udgave: udgiveren er ikke et emneord',
               navn=fil)
    # Identifikator uden foreningspræfiks.
    s = erstat(s, r"\(prefix && name\) \? \(prefix \+ '-", "(name) ? ('", navn=fil)

    # --- Temaer: kun Vinter og Sommer ---
    s = erstat(s, r'const THEMES = \{.*?\n\};', THEMES, navn=fil)
    s = (s.replace('THEMES.original', 'THEMES.vinter')
          .replace(": 'original';", ": 'vinter';")
          .replace("theme:'original',", "theme:'vinter',")
          .replace("data.theme || 'original'", "data.theme || 'vinter'")
          .replace("selectTheme('original');", "selectTheme('vinter');"))
    if "'original'" in s:
        sys.exit(f"{fil}: der står stadig 'original' et sted")
    labels = re.findall(r'( *)<label class="theme-option" data-theme="(\w+)".*?</label>\n', s, re.S)
    if not labels:
        sys.exit(f'{fil}: fandt ikke temavælgeren')
    indryk = labels[0][0]
    nye = ''
    for noegle, navn, accent, prikker in VAELGER:
        swatches = ''.join(f'<span class="swatch" style="background:{c}"></span>' for c in prikker)
        nye += (f'{indryk}<label class="theme-option" data-theme="{noegle}" style="--theme-accent:{accent}">\n'
                f'{indryk}  <input type="radio" name="theme" value="{noegle}"{" checked" if noegle == "vinter" else ""}>\n'
                f'{indryk}  <div class="swatches">{swatches}</div>\n'
                f'{indryk}  <div class="tname">{navn}</div>\n'
                f'{indryk}</label>\n')
    s = erstat(s, r'( *<label class="theme-option" data-theme="\w+".*?</label>\n)+', nye, navn=fil)

    # --- Foreningernes særlige ting ---
    if fil == 'side-bygger.html':
        s = erstat(s, r"const SERIES = \[.*?\];", "const SERIES = [];  // fri udgave: ingen faste serier", navn=fil)
        s = erstat(s, r'\s*<option value="Gader og veje">Gader og veje</option>'
                      r'\s*<option value="Steder, institutioner og mennesker">Steder, institutioner og mennesker</option>',
                   '', navn=fil)
    s = s.replace('Udgiver / område', 'Udgiver')
    s = s.replace('placeholder="F.eks. Foreningen ejer rettighederne. Må ikke gengives uden tilladelse."',
                  'placeholder="F.eks. Klassen ejer rettighederne. Må ikke gengives uden tilladelse."')

    # --- Navn og version ---
    s = erstat(s, r'^<title>([^<]*)</title>', r'<title>\1 · fri udgave</title>', navn=fil, flags=re.M)
    s = erstat(s, r'(· version [0-9.]+)</span>', r'\1 · fri udgave</span>', navn=fil)

    (UD / fil).write_text(s, encoding='utf-8')
    print(f'Lavet: fri/{fil}')


if __name__ == '__main__':
    UD.mkdir(exist_ok=True)
    for f in BYGGERE:
        lav(f)
