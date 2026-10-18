# Billedarkiv

Billedarkiv er et lille program til at ordne billeder: lægge dem i albums, give dem titel, beskrivelse, fotograf og årstal og sætte dem i den rigtige rækkefølge. Et album kan gemmes som en zip-fil, som **Album · Bygger** kan lave et færdigt billedalbum ud af.

Hele programmet er én fil, `billedarkiv.html`. Den virker uden internet. Skrifttyper og det, der skal til for at lave zip-filer, ligger inde i filen.

Version 1.1.

## Det skal du bruge

- **Chrome eller Edge.** Billedarkiv gemmer direkte i en mappe på din computer, og det kan kun de to browsere. I Safari og Firefox kan du se billederne, men ikke gemme dine oplysninger i mappen. Billedarkiv siger selv til.
- **En mappe med billeder**, fx på computeren, et USB-stik eller i pCloud.

## Sådan kommer du i gang

1. Åbn `billedarkiv.html` i Chrome eller Edge.
2. Vælg din arkivmappe. Browseren spørger, om Billedarkiv må gemme i mappen. Svar ja.
3. Billederne i mappen dukker op. Lav albums, og skriv titel, beskrivelse, fotograf og årstal på billederne.
4. Alt, hvad du skriver, bliver gemt i filen **`arkiv-data.json`** i selve arkivmappen. Næste gang du åbner Billedarkiv, kan du vælge den samme mappe igen, og så er det hele der.

Billeder, du ikke vil have med, kan du flytte til **Frasorteret / Papirkurv**. Så bliver de skjult i arkivet, men billedfilerne bliver liggende i mappen.

## Fra Billedarkiv til et færdigt album

1. Vælg albummet i Billedarkiv, og tryk **Download Album (ZIP)**.
2. Pak zip-filen ud. Den indeholder billederne, en `.json`-fil ved hvert billede og to oversigtsfiler (`_album-oversigt.json` og `_album-oversigt.txt`).
3. Åbn `album-bygger.html`, og vælg **alle billeder og alle `.json`-filer på én gang**.
4. Album · Bygger henter selv oplysningerne:

| I Billedarkiv | Bliver til i albummet |
|---|---|
| titel | billedets titel |
| beskrivelse | billedets beskrivelse |
| fotograf | Ophav / skaber (flere adskilt af semikolon) |
| årstal | 1. januar det år, så knappen **Sortér efter optagelsesdato** virker |
| rækkefølge | rækkefølgen i albummet |
| albummets navn | albummets titel |

Det, du selv har skrevet i Album · Bygger, bliver aldrig overskrevet. Oversigtsfilerne springes over, fordi de blot gentager oplysningerne. Reglerne står i `builder-standard.md`, afsnit 8.1.

## Husk backup

Dine oplysninger ligger i `arkiv-data.json` sammen med billederne. Tag backup af **hele arkivmappen** efter 3-2-1-reglen: tre kopier, på to slags medier, og én af dem et andet sted (fx hos et familiemedlem eller i foreningen). Billedarkiv gemmer også en ekstra kopi i browseren, men den forsvinder, hvis browserens data bliver slettet, så den er ikke en backup.

## Kun på din egen computer

Billedarkiv kører kun lokalt. Der fandtes en webhotel-udgave med en lille PHP-fil (`api.php`), men den er fjernet i version 1.1, fordi den ikke havde nogen adgangskode. Billedarkiv kontakter ikke nogen server.

## Hænger sammen med

- **Album · Bygger** (`album-bygger.html`) læser Billedarkivs eksport. Ændres formatet i eksporten (navnene på felterne i `.json`-filerne), skal Album · Bygger rettes med.
