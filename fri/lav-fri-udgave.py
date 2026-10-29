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
- De fælles emneord (tools/emneord.html) bliver til frie forslag i fri/emneord.html.
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
           'praesentation-bygger.html', 'samling-bygger.html']

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
    if fil == 'foer-og-nu-bygger.html':
        # Før og Nu bygger sine Dublin Core-felter i JavaScript.
        s = erstat(s, r"\}else if\(key === 'publisher'\)\{\n.*?\n    \}else\{",
                   "}else if(key === 'publisher'){\n"
                   "      // Fri udgave: udgiveren skrives som almindelig tekst.\n"
                   "      input = document.createElement('input');\n"
                   "      input.type = 'text';\n"
                   "      input.placeholder = 'fx 7.b, Roskilde Lille Skole eller dit eget navn';\n"
                   "    }else{", navn=fil)
        s = erstat(s, r"input\.addEventListener\('change', invalidate\);[^\n]*",
                   "input.addEventListener('input', invalidate);\n      input.addEventListener('change', invalidate);", navn=fil)
        s = erstat(s, r"'vælg område, så filen kan filtreres i søgeindekset'",
                   "'skriv, hvem der står bag, fx en klasse, en skole eller dit navn'", navn=fil)
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

    # --- Temaer: kun Vinter og Sommer ---
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
        s = erstat(s, r'var THEME_NAMES = \{[^\n]*\};', "var THEME_NAMES = { vinter:'Vinter', sommer:'Sommer' };", navn=fil)
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


def lav_soegeside():
    fil = 'tools/soegeside-bygger.html'
    s = (ROD / fil).read_text(encoding='utf-8')
    s = erstat(s, r'"Søg i lokalhistorier"', '"Søg i samlingen"', antal=3, navn=fil)
    s = erstat(s, r'aria-label="Filtrér på område"><option value="">Alle områder</option>',
               'aria-label="Filtrér på udgiver"><option value="">Alle udgivere</option>', navn=fil)
    s = erstat(s, r'<option value="omraade">Område</option>', '<option value="omraade">Udgiver</option>', navn=fil)
    s = erstat(s, r'"Uden område \("', '"Uden udgiver ("', navn=fil)
    # Kun byggerens egen titel og version, ikke titlen i den søgeside, den laver.
    s, n = re.subn(r'^<title>([^<]*)</title>', r'<title>\1 · fri udgave</title>', s, count=1, flags=re.M)
    s = erstat(s, r'(Søgeside · Bygger — version [0-9.]+)', r'\1 · fri udgave', navn=fil)
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
KOPIER = ['tools/feltoptager.html', 'tools/lydlab.html', 'tools/fotolab.html', 'tools/skyggeramme.html']


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
