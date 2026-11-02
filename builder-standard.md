# Builder-standard

**Fælles specifikation for værktøjskassen**
Version 2.21 · november 2026

Referenceimplementering: **Sidebygger** (`side-bygger.html`, version 1.5).
Når noget er i tvivl, er det referencefilen der gælder.

---

## 1. Hvad standarden dækker

Værktøjskassen består af flere små, uafhængige builder-apps:

| App | Genererer | dc.type |
|---|---|---|
| Sidebygger | artikelside om gader, steder og mennesker | Text |
| Lydfortælling · Bygger | lydafspiller med billede og tekst | Sound |
| Album · Bygger | billedalbum med grid og lightbox | Image |
| Piwigo Albumbygger | billedalbum hentet fra et album i et Piwigo-galleri, ellers som Album · Bygger | Image |
| Bogbygger | e-bog / lydbog | Text |
| Enkeltbillede · Bygger | ét billede i høj kvalitet med tekst og Exif | Image |
| Før og Nu · Bygger | to billeder fra samme sted, rettet ind oven på hinanden, med skyder eller overtoning | Image |
| Mediekort · Bygger | kort til en video (YouTube eller videofil), en podcast eller et talende billede, med kapitler og evt. transskription | MovingImage (video), Sound (podcast og talende billede) |
| Præsentation · Bygger | billedshow til foredrag og oplæg, med pile, fuld skærm og automatisk visning | Image |
| Samling · Bygger | forside der binder 2-6 færdige filer sammen til en kurateret samling | Collection |
| Podcastserie · Bygger | én side med afspilningsliste til en serie på 2-6 lydafsnit, med lydfilerne i mappeformatet (5.7) | Sound |
| Henvisning · Bygger | "skilt" der sender videre til en ekstern side | Text som standard; vælges efter det, der henvises til |
| Vejledningsbygger | trin-for-trin vejledning | afklares |
| PWA · Bygger | samler færdige filer til en app til hjemmeskærmen | afklares |
| Metadata-hjælper | ingen fil; laver en AI-prompt til Dublin Core og kontrollerer svaret (se afsnit 11) | - |
| Galleriarkiv (`tools/galleriarkiv.html`, v1.0, se 5.8) | en mappe med et helt album fra Piwigo, med underalbum, billeder og tekster | Collection |

Bragt i overensstemmelse med standarden: Sidebygger (v1.5, `side-bygger.html`), Album · Bygger (v3.1, `album-bygger.html`, følger 2.3), Enkeltbillede · Bygger (v3.0, `enkeltbillede-bygger.html`, følger 1.9), Præsentation · Bygger (v1.0, `praesentation-bygger.html`, følger 2.1), Samling · Bygger (v2.0, `samling-bygger.html`, følger 1.5), Mediekort · Bygger (v1.2, `mediekort-bygger.html`, følger 1.6), Piwigo Albumbygger (v2.0, `piwigo-album-bygger.html`, følger 1.6), Henvisning · Bygger (v2.0, `henvisning-bygger.html`, følger 1.8), Bogbygger (v4.0, `bog-bygger.html`, følger 1.9), Før og Nu · Bygger (v2.0, `foer-og-nu-bygger.html`, følger 2.4) og Lydfortælling · Bygger (v4.0, `lydfortaelling-bygger.html`, følger 1.9).

Den Talende Booklet er udgået. Dens opgave, ét billede med lyd og tekst, løses nu af Mediekort · Bygger med typen **Talende billede**. Lydfortælling · Bygger består dog som den enkle udgave til dem, der kun skal bruge ét billede, lyd og tekst.

De løser hver sin opgave, men skal se ens ud, opføre sig ens og producere filer med samme metadata-struktur. Det er kun de dele, dette dokument beskriver. Alt andet må gerne være appspecifikt.

Piwigo Albumbygger afviger bevidst på ét punkt: den kører ikke lokalt uden server, men skal ligge på galleriets egen webadresse, fordi den henter albummets oplysninger og billeder fra Piwigos egen grænseflade (`ws.php`). Åbnes den direkte fra computeren, kan den stadig indlæse og rette et album, man allerede har lavet, men ikke hente fra Piwigo, og den siger det selv. Den færdige fil er som i alle andre byggere en selvstændig HTML-fil uden server. Byggeren kan desuden gemme de hentede originaler som ZIP-fil og albummets oplysninger som `album.json`.

Vejledningsbygger og PWA · Bygger afviger bevidst på enkelte punkter, fordi de ikke laver indhold på samme måde som de andre. Afvigelserne skal stå i appens egen beskrivelse.

Metadata-hjælper laver ingen outputfil og har derfor hverken temaer, forhåndsvisning af en side, indlæsning eller afsnit 5. Den følger standarden for builderens udseende (afsnit 2 og 3), `PUBLISHERS`, dc.type-listen og de 15 felter i afsnit 4.

Søgeside · Bygger (`tools/soegeside-bygger.html`, v1.4) og Søgeindeks · Online (`sbtv/`, den samme søgeside med login, upload og administration på et webhotel med PHP) er værktøjer, ikke byggere. De læser metadata fra byggernes filer (`builder-source-data` med de 15 Dublin Core-felter), men laver ikke selv en fil efter afsnit 5 og har derfor hverken temaer, Dublin Core-panel eller byggernes udseende. De følger standarden på disse punkter: `?hjem=` sendes med til filerne (5.3), `dc.publisher` vises som *Område* med valget **Uden område** (4.1), og en henvisning kendes på sin `link` og vises med 🔗, uanset `dc.type`. Kun `http(s)`-adresser bliver til links.

**Lokalhistorier · fri udgave** (`fri/`) er en udgave af byggerne til enkeltpersoner, skoler og institutioner uden for den fælles portal. Den laves af opskriften `fri/lav-fri-udgave.py` ud fra de almindelige byggere og følger standarden med disse undtagelser: `dc.publisher` er et tekstfelt i stedet for `PUBLISHERS` (4.1), der er kun temaerne Vinter og Sommer i stedet for afsnit 3's temaer, udgiveren sættes ikke ind som første emneord, og identifikatoren har intet præfiks, fx `album-sommerlejren-2026`. Filformatet er det samme som i de almindelige byggere.

Søgeindeks · Online lægger aldrig `config.php` og `data/` i repoet. Repoet er offentligt, og de to dele indeholder adgangskoder og driftsdata. Brug `sbtv/config-eksempel.php` som skabelon.

Ældre filer, der er lavet før en bygger blev bragt i overensstemmelse med standarden, laves om i den nye bygger. Byggerne skal ikke bære rundt på kode til at forstå gamle formater.

### Grundprincipper

1. **Én HTML-fil.** Builderen kører lokalt i browseren uden server. Outputfilen indeholder alt (billeder og lyd som base64) og kan åbnes uden internet. Undtagelsen er mediefiler, der er for store til at ligge indeni, fx video og lange lydoptagelser. De lægges i en mappe ved siden af HTML-filen efter afsnit 5.7. Én historie er én fil, når det kan lade sig gøre, og én mappe, når det skal.
2. **Målgruppen er ældre og ikke nødvendigvis teknisk øvede.** Stor skrift, høj kontrast, tydelige knapper.
3. **Filerne skal kunne læses om 20 år.** Ren HTML, ingen eksterne afhængigheder, metadata efter Dublin Core.

### 1.1 Bevaringsstrategi og backup (3-2-1-reglen)

Selv den mest holdbare HTML-fil har ingen værdi, hvis filen forsvinder fysisk. Alle guider, workshops og brugerflader skal fremhæve 3-2-1-princippet:

- **3 kopier:** den originale fil plus mindst 2 kopier.
- **2 medietyper:** mindst to forskellige lagringsmedier (fx computerens harddisk og et USB-stik).
- **1 off-site:** mindst én kopi opbevaret et andet sted (fx hos et familiemedlem eller i et foreningsarkiv).

### 1.2 Hvor tingene ligger

Standarden og byggerne ligger i ét GitHub-repo:

```
builder-standard.md      ← den officielle udgave
byggere/                 ← én fil pr. bygger, kun nyeste version
arkiv/                   ← gamle versioner
eksempler/               ← en færdig testfil fra hver bygger
```

**Filnavne på byggerne:** `navn-bygger.html`, med små bogstaver, bindestreg mellem ordene og uden æ, ø og å, fx `side-bygger.html`, `album-bygger.html` og `lydfortaelling-bygger.html`. Versionsnummeret står i byggerens topbar, ikke i filnavnet, så adressen er den samme fra version til version.

---

## 2. Layout i builderen

```
┌──────────────────────────────────────────────┐
│ Topbar: Appnavn + linje om hvad den gør,     │
│         "kører lokalt, ingen server", version │
├───────────────────────────┬──────────────────┤
│ Formular-rude (max 660px) │ Forhåndsvisning  │
│                           │ (420px, sticky)  │
│ felter …                  │ iframe med       │
│ Dublin Core (foldet ind)  │ live output      │
│ [ Generér … (.html) ]     │                  │
│ besked-boks               │                  │
└───────────────────────────┴──────────────────┘
```

Grid: `1fr 420px`, falder til én kolonne under 900 px.

Forhåndsvisningen opdateres ved hvert tastetryk (`renderPreview()`) og bygges med samme funktion som den endelige fil, ellers driver de fra hinanden. Tællerkoden udelades bevidst i forhåndsvisningen.

Apps med mange eller store billeder må tegne selve rammen med en kort forsinkelse (højst 0,3 sekund), så skrivningen ikke bliver tung. Felter, Dublin Core og størrelsesvisning opdateres stadig med det samme. Forhåndsvisningen må også vise et begrænset antal billeder (Album · Bygger viser de første 24) med en kort note om, at alle kommer med i den færdige fil.

Topbarens overskrift er appens navn i Georgia 25 px. Versionsnummeret står i underlinjen, fx "… · kører lokalt, ingen server · version 1.4". Browserfanens `<title>` er appens navn.

### Rækkefølge af felter

1. Indlæs eksisterende fil (valgfri), altid øverst
2. Tema
3. Appens egne indholdsfelter (mærkat, titel, sted, billede, lyd, tekst …)
4. Knapper i bunden (valgfri CTA: knaptekst + URL)
5. Hjem-knap (se 5.3)
6. "Nederst på den færdige side": afkrydsning for **Gem filen** og **Om denne fil**
7. Dublin Core-metadata, sammenfoldet `<details>`-panel
8. Generér-knap + beskedboks

---

## 3. Design-tokens

### Farver i builderens egen brugerflade

```
--paper:     #EDE7D8  /* baggrund */
--paper-dim: #E2DBC8  /* forhåndsvisningsrude */
--ink:       #211E19  /* brødtekst */
--rust:      #9A4630  /* accent, knapper, fokus */
--moss:      #47543C  /* feltlabels */
--hint:      #5F5A4B  /* hjælpetekster */
--line:      #cbc2a8  /* streger og rammer */
--serif: Georgia, 'Iowan Old Style', 'Palatino Linotype', Palatino, serif;
--sans:  -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
```

Builderens egen brugerflade har altid disse farver, uanset hvilket tema der er valgt til outputfilen.

### Skriftstørrelser i builderen, minimum

| Element | Størrelse |
|---|---|
| Topbar-overskrift | 25 px, Georgia |
| Feltlabel | 13,5 px, halvfed, versaler, spatiering 0,09em |
| Hjælpetekst under felt | 13,5 px, linjehøjde 1,55 |
| Inputfelt og textarea | 15,5 px |
| Dublin Core-feltlabel | 14 px |
| Dublin Core-hjælpetekst | 13 px |
| Filboks og status | 14,5 / 13,5 px |
| Generér-knap | 17 px, fed |
| Beskedboks | 14,5 px |

Kontrastkrav: al tekst mindst 4,5:1 mod sin baggrund.

Teksten ved afkrydsningsfelter skrives med almindelige bogstaver i 14,5 px, ikke med versaler som feltlabels. Afkrydsningsfelter og radioknapper bruger accentfarven `--rust`.

### Temaerne til outputfilen

Syv temaer, samme nøgler og samme værdier i alle apps. Vælges med store farvede radioknapper med farveprikker, i denne rækkefølge. Hvert tema har både lys og mørk udgave (`d`-præfiks = mørk tilstand).

| Nøgle | Navn i vælgeren | Accent |
|---|---|---|
| `original` | Oprindeligt | #9A4630 |
| `historisk` | Historisk Samfund | #385261 |
| `lokalhistorisk2` | Lokalhistorisk | #9E2453 |
| `roedekors` | Røde Kors | #E30A0B |
| `roskildetv` | Roskilde TV | #B03035 |
| `roskildemusik` | Roskilde Musik | #C2410C |
| `cirkler` | cirkler | #7351C4 |

`label` i `THEMES` er temaets fulde beskrivelse. Vælgeren viser det korte navn fra tabellen, så knapperne kan stå på én linje.

Den fulde definition kopieres uændret ind i hver bygger:

```js
const THEMES = {
  original: {
    label:'Oprindeligt',
    accent:'#9A4630', accentDark:'#82392A', onAccent:'#FDF8EE',
    paper:'#EDE7D8', surface:'#FBF8EF', ink:'#211E19', body:'#3A362E',
    muted:'#6B6455', line:'#D8D0BA', quote:'#5A5646',
    dPaper:'#171613', dSurface:'#201E1A', dInk:'#EDE7D8', dBody:'#DDD5C4',
    dMuted:'#A79E8B', dLine:'#3A3630', dQuote:'#BDB5A3'
  },
  historisk: {
    label:'Historisk Samfund',
    accent:'#385261', accentDark:'#2A3D49', onAccent:'#FFFFFF',
    paper:'#F2F5F6', surface:'#FFFFFF', ink:'#16232A', body:'#2A3A42',
    muted:'#5F707A', line:'#DDE3E6', quote:'#4A5C66',
    dPaper:'#151D22', dSurface:'#1D272D', dInk:'#E9EEF1', dBody:'#DBE3E8',
    dMuted:'#A5B5BE', dLine:'#32414B', dQuote:'#B8C6CD'
  },
  lokalhistorisk2: {
    label:'Lokalhistorisk (bordeaux)',
    accent:'#9E2453', accentDark:'#7A1B40', onAccent:'#FAF9F5',
    paper:'#F3EFEF', surface:'#FFFFFF', ink:'#2A1620', body:'#332028',
    muted:'#6B5560', line:'#E4DADE', quote:'#5C3B48',
    dPaper:'#1A1116', dSurface:'#241820', dInk:'#F7EFF2', dBody:'#E9DDE2',
    dMuted:'#B9A4AD', dLine:'#3D2C34', dQuote:'#C7AEB8'
  },
  roedekors: {
    label:'Røde Kors',
    accent:'#E30A0B', accentDark:'#B80809', onAccent:'#FFFFFF',
    paper:'#F5F5F5', surface:'#FFFFFF', ink:'#1A1A1A', body:'#2B2B2B',
    muted:'#5A5A5A', line:'#E2E2E2', quote:'#333333',
    dPaper:'#161616', dSurface:'#1F1F1F', dInk:'#F2F2F2', dBody:'#E0E0E0',
    dMuted:'#AAAAAA', dLine:'#383838', dQuote:'#C4C4C4'
  },
  roskildetv: {
    label:'Roskilde TV',
    accent:'#B03035', accentDark:'#8A2429', onAccent:'#FFFFFF',
    paper:'#F4F4F4', surface:'#FFFFFF', ink:'#141414', body:'#222222',
    muted:'#555555', line:'#E2E2E2', quote:'#333333',
    dPaper:'#141414', dSurface:'#1E1E1E', dInk:'#F2F2F2', dBody:'#DEDEDE',
    dMuted:'#A8A8A8', dLine:'#363636', dQuote:'#C2C2C2'
  },
  roskildemusik: {
    label:'Roskilde Musik',
    accent:'#C2410C', accentDark:'#9A3412', onAccent:'#FFFFFF',
    paper:'#F6F5F3', surface:'#FFFFFF', ink:'#1C1C1F', body:'#2A2A2E',
    muted:'#55555C', line:'#E4E2DE', quote:'#3F3F46',
    dPaper:'#141416', dSurface:'#1E1E21', dInk:'#F4F4F5', dBody:'#E0E0E3',
    dMuted:'#A6A6AD', dLine:'#36363B', dQuote:'#C4C4CA'
  },
  cirkler: {
    label:'cirkler',
    accent:'#7351C4', accentDark:'#5A3DA3', onAccent:'#F2FA5A',
    paper:'#FBF9F5', surface:'#FFFFFF', ink:'#111111', body:'#222222',
    muted:'#55555C', line:'#E6E1F0', quote:'#3A3A3A',
    dPaper:'#141317', dSurface:'#1E1C24', dInk:'#F4F2F8', dBody:'#E2DFE8',
    dMuted:'#AAA6B4', dLine:'#38343F', dQuote:'#C8C4D2'
  }
};
```

Roskilde TV's farveprik i vælgeren er flerfarvet (logoets seks prikfarver). Mediekort · Bygger må desuden vise prikrækken i selve kortet.

Roskilde Musiks farver kommer fra foreningens logo: orange, næsten sort og grå. Logoets klare orange (`#FF5500`) er for lys til hvid tekst (kontrast 3,2), så accenten er en mørkere, brændt orange (`#C2410C`, kontrast 5,2 med hvid). Den klare orange bruges kun i farveprikken, som er en lille plade: `radial-gradient(circle, #FF5500 0 30%, #1C1C1F 33% 100%)`.

cirklers farver kommer fra fællesskabets logo: gul `#F2FA5A`, lyseblå `#BCE3EC`, lavendel `#DEC9FF`, creme `#FBF9F5` og sort. De tre lyse farver er for lyse til tekst, så accenten er en dyb lilla (`#7351C4`) med logoets gule farve som tekst på accenten (kontrast 5,0). Baggrunden er logoets creme. Farveprikken i vælgeren er tre små cirkler med sort kant, ligesom i logoet. Mediekort · Bygger viser de tre cirkler i kortet og på den tegnede plakat, på samme måde som Roskilde TV's prikker.

Temaet skrives ud som CSS-variabler i `:root` i den genererede fil, aldrig som faste farver inde i reglerne. Kommer et nyt tema til, tilføjes det her først og kopieres derefter til alle byggere.

---

## 4. Dublin Core

Samme 15 felter, samme rækkefølge og samme labels i alle apps. Hjælpeteksten starter altid med feltnavnet (fx `dc.creator —`) og må derefter tilpasses appen (fx "den der har skrevet siden" i Sidebyggeren).

| # | Felt | Label | Standard-hjælpetekst | Standard |
|---|---|---|---|---|
| 1 | dc.title | Titel | udfyldes automatisk fra titlen | (auto) |
| 2 | dc.creator | Ophav / skaber | den der har skabt materialet | |
| 3 | dc.subject | Emne / nøgleord | adskil med semikolon | |
| 4 | dc.description | Beskrivelse | udfyldes automatisk | (auto) |
| 5 | dc.publisher | Udgiver / område | vælg område, så filen kan filtreres i søgeindekset | tom |
| 6 | dc.contributor | Bidragyder | den der har redigeret, fotograferet eller registreret | |
| 7 | dc.date | Dato | helst ÅÅÅÅ-MM-DD, men fx "ca. 1955" er også i orden | |
| 8 | dc.type | Type | DCMI-typeordforråd | appens egen |
| 9 | dc.format | Format | filens tekniske format | text/html |
| 10 | dc.identifier | Identifikator | foreslås automatisk ud fra område og titel | (auto) |
| 11 | dc.source | Kilde | hvor oplysningerne stammer fra | |
| 12 | dc.language | Sprog | sprogkode, fx da | da |
| 13 | dc.relation | Relation | beslægtet materiale | |
| 14 | dc.coverage | Dækning | sted og/eller periode, udfyldes automatisk fra Sted | (auto) |
| 15 | dc.rights | Rettigheder | ophavsret og vilkår for brug | |

Dropdown til dc.type, i denne rækkefølge: Text, Image, Sound, MovingImage, InteractiveResource, Collection, Event. Hver app forvælger sin egen.

### Regler

- Alle felter er valgfrie. Tomme felter udelades helt af outputfilen.
- Fire felter udfyldes automatisk, indtil man selv skriver i dem (`dcTouched`-flag): title, description, coverage og identifier. description afstribes for Markdown og forkortes til 300 tegn.
- subject deles ved semikolon og skrives som ét `<meta>`-tag pr. nøgleord.
- Panelet er sammenfoldet som standard.
- Knappen **Gem metadata som separat JSON-fil** giver `<slug>-metadata.json`:
  `{ "@context": "http://purl.org/dc/elements/1.1/", "dublinCore": { … }, "generated": "…Z" }`

I den genererede fil skrives metadata to steder:

1. I `<head>`: `<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/">` efterfulgt af `<meta name="DC.xxx" content="…">` i feltrækkefølgen. Dette sker altid.
2. Nederst på siden: et udfoldeligt panel **Om denne fil** med label, teknisk feltnavn i småt og værdien, skrevet som færdig HTML. Kan slås fra (se 5.4).

Sidens `<html lang="…">` sættes fra dc.language, ellers `da`.

### 4.1 Udgiver og område

Hver fil skal kunne henføres til præcis én udgiver, så en samling kan filtreres, pakkes og videregives uden manuel gennemgang.

dc.publisher vises som en rulleliste, aldrig som frit tekstfelt, med standardvalget "(ikke valgt)". Listen ligger som ét `PUBLISHERS`-array øverst i scriptet og kopieres uændret til alle byggere:

```js
const PUBLISHERS = [
  { name:'cirkler',                                 prefix:'CIR'  },
  { name:'Historisk Samfund for Roskilde Amt',      prefix:'HSR'  },
  { name:'Privat',                                  prefix:'PRIV' },
  { name:'Roskilde Musik',                          prefix:'RMU'  },
  { name:'Roskilde TV',                             prefix:'RTV'  },
  { name:'Røde Kors',                               prefix:'RKR'  },
  { name:'Syd for Banen - Lokalhistorisk Forening', prefix:'SFB'  }
];
```

Værdierne gengives tegn for tegn, med **almindelig bindestreg**. Afvigende stavemåder (tankestreg, "Røde Kors Roskilde", "SFB", "privat" med lille p) skaber nye grupper i søgesiden og regnes som fejl.

Indlæses en fil med en gammel værdi, rettes den til den gældende: tankestreg bliver til bindestreg, og "Røde Kors Roskilde" bliver til "Røde Kors". Status ved indlæsning nævner rettelsen. Står der et helt ukendt navn, vises det som ekstra valg markeret "ikke på listen", så det ikke går tabt, men kan rettes.

**dc.identifier** foreslås automatisk som `PRÆFIKS-type-titelslug-år`, fx `SFB-side-astersvej-2026` eller `SFB-album-skomagervaerkstedet-paa-algade-2026`. Titelslug laves efter reglen i 5.6. `type` er et kort, fast ord for appen (side, lyd, album, bog, billede, foernu, video, podcast, talende, praesentation, samling, henvisning, serie). Mediekort · Bygger bruger `video` til video og videofil, `podcast` til podcast og `talende` til et talende billede, fx `SFB-talende-bager-hansens-butik-2026`. Forslaget kan overskrives.

**dc.subject:** udgiveren står som første emneord. En app med faste serier (fx Sidebyggerens "Gader og veje" og "Steder, institutioner og mennesker") indsætter serien som andet emneord. Derefter kommer de fælles emneord fra `emneord.md`, som alle foreninger staver ens, og til sidst de mere præcise emneord.

`Syd for Banen - Lokalhistorisk Forening; Gader og veje; Astersvej; velfærdsbyggeri`

**dc.rights** beskriver, hvad andre må med filen, og udfyldes uafhængigt af udgiveren. Typiske værdier:

| Udgiver | Typisk dc.rights |
|---|---|
| Privat | Privat materiale. Må ikke videregives. |
| Syd for Banen | © Syd for Banen - Lokalhistorisk Forening. Må gengives med kildeangivelse. |
| Røde Kors | © Røde Kors. Må anvendes i foreningens formidling. |

**Adskillelse på disken:** arbejdsfilerne ligger i én mappe pr. udgiver, og en distributionspakke bygges fra én mappe ad gangen.

**Søgesiden** bygger sit udgiverfilter ud fra de værdier, der faktisk findes i samlingen. Filer uden udgiver samles under **Uden område** og rettes ved kilden.

**Nye udgivere** tilføjes i `PUBLISHERS` ovenfor, før de tages i brug, derefter i alle byggere og i prompten i afsnit 11.

---

## 5. Outputfilens kontrakt

### 5.1 Kildedata

Hver genereret fil indeholder en JSON-blok, der gør filen redigerbar igen:

```html
<script type="application/json" id="builder-source-data">
{ "format": "sidebygger-side",
  "builderVersion": 1,
  "theme": "roedekors",
  "title": "…",
  "dublinCore": { … } }
</script>
```

- `format` er appens eget navn (sidebygger-side, lydfortaelling-player, billedalbum, ebog, foer-og-nu …).
- `builderVersion` er et heltal, der tælles op ved formatændringer.
- `theme` er en af nøglerne fra afsnit 3.
- Blokken indeholder den rå Markdown, ikke færdig HTML.
- Billeder og lyd hører ikke til i JSON-blokken. De læses tilbage fra dokumentet.

### 5.2 Øvrigt indhold i `<head>`

- og:type, og:title, og:description altid. og:image + twitter:card kun hvis **Webadresse til delingsbillede** er udfyldt.
- `<link rel="icon">` sat til det indlejrede billede. Byggeren laver helst et lille ikon (fx 64 × 64 px) ud fra billedet, så det store billede ikke ligger i filen to gange.

### 5.3 Hjem-knap

Øverst i outputfilen kan der stå en hjem-knap. Builderen har tre valg:

- **Automatisk** (standard): knappen vises kun, når filen er del af noget større. Filen viser den, hvis den er åbnet som installeret app, hvis man kom fra en side i samme mappe, eller hvis der ligger en `index.html` ved siden af (online). Åbnes filen alene, bliver knappen væk.
- **Altid vist**
- **Aldrig vist**

Knaptekst (standard "Hjem") og adresse (standard `index.html`) kan ændres. En adresse i URL'en (`?hjem=…`) går forud for den indbyggede, og knappen vises så altid. I forhåndsvisningen vises knappen altid.

**Adressen i `?hjem=` skal kontrolleres.** Den må kun være en relativ adresse (fx `soegeside.html?q=lyd`) eller begynde med `http://` eller `https://`. Alt andet, fx `javascript:` og `data:`, ignoreres, som om der ikke stod noget. Mellemrum, tabulator og andre styretegn fjernes først, fordi browsere ser bort fra dem i en adresse. Ellers kunne et fabrikeret link få knappen til at køre kode på sitet. Det gælder alle byggere og er kontrolleret i dem alle.

Det er sådan en samling fører tilbage til sig selv: Samling · Bygger sender sit eget filnavn med i linket til hver fil (`historie.html?hjem=samling.html`). Filerne skal derfor ikke laves om for at kunne indgå i en samling, men alle byggere skal læse `?hjem=` som beskrevet her. En samlings egen hjem-knap i "Automatisk" vises ikke, når man kommer tilbage fra en af samlingens egne filer.

### 5.4 Nederst i filen

- **Gem filen**-knap: pilikon i accentfarven + teksten **Gem filen** (fed), pilleform (`border-radius:999px`) med 2 px ramme, mindst 48 px høj, fyldes ved hover, 3 px fokus-outline. Under knappen en kort kursiveret linje om hvad filen er, fx "Et billedalbum. Gemmer hele albummet som én fil på din egen computer." Knappen gemmer en ren kopi: mørk tilstand, skriftstørrelse og op-pil nulstilles før gem.
- **Om denne fil**-panelet (se afsnit 4).
- Begge kan slås fra i builderen, fx når siden vises inde på et website. Dublin Core i `<head>` følger altid med.
- Manuelt indsat tællerkode mellem `TÆLLER` og `TÆLLER SLUT` bevares uændret. Builderen skriver aldrig selv tællerkode.

### 5.5 Læsehjælp i outputfilen

- **Mørk tilstand**: knap i toppen, bruger temaets `d`-farver.
- **Skriftstørrelse**: knapper til mindre og større skrift, skala 0,85-1,7 i trin af 0,1 via `--fs`-variablen. Al typografi i outputfilen er i `em` eller ganges med `--fs`.
- **Op-pil**: rund knap nederst til højre i accentfarven, 48 × 48 px, vises efter 400 px scroll.
- Ved udskrift skjules værktøjslinje, op-pil og gem-knap.

Korte outputfiler (fx et enkelt billede) må udelade skriftstørrelse og op-pil.

### 5.6 Filnavn

Slug af titlen: små bogstaver, **æ→ae, ø→oe, å→aa**, derefter fjernes accenter (é→e, ü→u), og alt andet end a-z0-9 bliver til bindestreg. Samme funktion bruges til filnavn og til titelslug i dc.identifier, så de altid passer sammen.

```js
function slugify(s, fallback){
  const out = (s || '').toLowerCase()
    .replace(/æ/g,'ae').replace(/ø/g,'oe').replace(/å/g,'aa')
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
  return out || fallback || '';
}
```

Eksempel: "Skomagerværkstedet på Algade" → `skomagervaerkstedet-paa-algade.html`.

Filer, der er lavet før version 1.2, beholder deres filnavn og identifikator. Reglen gælder kun nye filer, og en gammel identifikator overskrives ikke, når filen indlæses igen.

### 5.7 Mappeformat: når mediefilerne er for store

Video og lange lydoptagelser kan ikke ligge inde i HTML-filen. Base64 gør filen en tredjedel større, browseren skal have det hele i hukommelsen, og en time video fylder nemt 1-3 GB. Til dem bruges **mappeformatet**: HTML-filen er stadig forsiden og bærer stadig sedlen, men mediefilerne ligger ved siden af i en mappe.

**Hvornår:** Video lægges altid i en mappe eller hentes online. Lyd lægges ind i filen som hidtil, så længe hver lydfil er under ca. **50 MB** (ca. 50 minutter mp3 i 128 kbit/s). Større lydfiler og serier med flere lydfiler, fx en podcastserie, bruger mappeformatet.

**Opbygning:**

```
foraarsmoedet-2026/                  ← mappen hedder som HTML-filen (5.6)
  foraarsmoedet-2026.html            ← forsiden: tekst, kapitler, plakatbillede og sedlen
  media/
    foraarsmoedet-2026.mp4           ← én mediefil, eller fx …-01.mp3, …-02.mp3 til en serie
```

**Regler:**

1. **Relative adresser.** HTML-filen peger på mediefilerne med en relativ adresse, fx `media/foraarsmoedet-2026.mp4`. Aldrig en sti på computeren som `C:\…` eller `file://`. Så virker mappen alle steder: på en USB-nøgle, på et drev, på webhotellet og pakket ud af en zip-fil.
2. **Filnavne efter 5.6:** små bogstaver, bindestreger, ingen æ, ø og å og ingen mellemrum. Mediefilerne hedder som HTML-filen, med et løbenummer, hvis der er flere.
3. **HTML-filen kan stå alene.** Plakatbillede, tekst, kapitler og sedlen ligger stadig inde i HTML-filen. Kan mediefilen ikke findes, vises en tydelig besked i stedet for afspilleren, fx "Videoen ligger i filen *media/foraarsmoedet-2026.mp4* i samme mappe som denne fil." Siden må aldrig bare være tom.
4. **Reserve online.** Hver mediefil kan have én reserveadresse, der begynder med `https://`, fx et link til samme fil i pCloud. Afspilleren prøver først den lokale fil og derefter reserven. Så virker den samme HTML-fil både fra mappen til et møde og alene på en hjemmeside eller i søgeportalen. Reserveadressen følger reglerne for adresser i 5.3: kun `https://`, aldrig `javascript:` eller `data:`.
5. **Faste formater.** Video: **MP4 med H.264-billede og AAC-lyd**, som kan afspilles i alle almindelige browsere. Lyd: **MP3**. Videoer fra iPhone (HEVC eller .mov), DVD (VOB) og videobånd konverteres, før de bruges i byggeren. Byggeren advarer, når den kan se, at en fil ikke er i et af de faste formater.
6. **Sedlen kender filerne.** Kildedata (5.1) får listen `media` med én post pr. mediefil: relativ adresse, MIME-type, størrelse i bytes, varighed i sekunder og eventuel reserveadresse. Panelet **Om denne fil** (5.4) viser listen som "Hører til denne fil". Byggeren beregner et **kontroltal (SHA-256)**, filens "fingeraftryk", og skriver det med som `sha256`. Det beregnes i bidder, så også en film på flere GB kan klares uden at fylde hukommelsen. Vælger man mediefilen igen til et indlæst kort, sammenligner byggeren kontroltallene og siger, om det er præcis den samme fil.

   ```json
   "media": [
     { "fil": "media/foraarsmoedet-2026.mp4", "type": "video/mp4",
       "bytes": 1288490188, "varighed": 3725,
       "reserve": "https://…/foraarsmoedet-2026.mp4",
       "sha256": "b6721c22ff0219d7…" }
   ]
   ```
7. **Udvalgte stykker** (blød klipning) virker på samme måde med en lokal fil som med en adresse på nettet.
8. **Transport.** Mappen kan pakkes som en zip-fil uden komprimering. Zip-filen hedder som mappen. Video og mp3 er allerede komprimeret, så pakningen går hurtigt og sparer ingen plads ved at komprimere.

**Krav til den, der viser filen,** fx ved et medlemsmøde: Kopiér altid **hele mappen**, ikke kun HTML-filen. Åbn HTML-filen direkte fra mappen, og prøv afspilningen på den computer, der skal bruges, før mødet. Så afhænger visningen hverken af internet, wifi eller reklamer.

**Søgeportalen** tager indtil videre kun imod HTML-filer. En historie i mappeformat lægges op som HTML-filen alene, og mediet afspilles fra reserveadressen. Uden reserve viser filen beskeden fra regel 3.

### 5.8 Galleriarkiv: et helt album fra Piwigo i en mappe

**Status: vedtaget i november 2026. Værktøjet Galleriarkiv 1.0 er lavet** og er prøvet mod en testudgave af Piwigo. Det prøves nu på albummet Roskilde Domkirke (https://sfbbilleder.eu/index.php?/category/48) med 8 underalbum og 572 billeder. Afsnittet beskriver, hvordan mappen skal se ud, så den kan læses og bruges om mange år, også af andre end dem, der har lavet den.

**Formål.** Galleriet i Piwigo skal kunne leve videre, også når ingen kan passe webhotellet. Piwigo kræver PHP, en database og løbende opdateringer. Et galleriarkiv kræver ingenting: Det er en almindelig mappe med billeder og HTML-sider, som alle kan åbne med et dobbeltklik, lægge på et USB-stik eller en disk og lægge på et hvilket som helst webhotel med FTP. Det er en **redningsbåd** og ikke en sikkerhedskopi af selve Piwigo.

**Tre lag.** Galleriarkivet erstatter ikke en rigtig sikkerhedskopi, men supplerer den (se også 3-2-1-reglen i 1.1):

| Lag | Hvad | Hvem kan bruge det |
|---|---|---|
| Sikkerhedskopi af Piwigo | mappen med billeder hentet med FTP **og** en kopi af databasen (fx eksport med phpMyAdmin). Begge dele skal med: Titler, beskrivelser og album ligger kun i databasen, og billedfilerne har kryptiske navne. | den, der kan sætte et webhotel op |
| Galleriarkiv | udvalgte album med underalbum, billeder i 1600-2000 px og alle tekster | alle, med et dobbeltklik |
| Online-galleriet | Piwigo, som i dag | alle på nettet |

**Værktøjet** er én HTML-fil, `tools/galleriarkiv.html`. Ligesom Piwigo Albumbygger skal den ligge på galleriets egen adresse for at kunne hente fra Piwigo (`ws.php`). Den bygger ikke én fil, men en mappe, og afviger derfor fra grundprincip 1. Album · Bygger og Piwigo Albumbygger beholder deres opgave: små album i én fil.

**Opbygning.** Hvert album bliver en mappe, og hvert underalbum bliver en mappe inde i den. Hver mappe har den samme opbygning, så et underalbum kan tages ud og stå alene.

```
roskilde-domkirke/                ← hovedalbummet, navn efter 5.6
  LAES-MIG.txt                    ← kort forklaring i almindelig tekst (kun i den øverste mappe)
  index.html                      ← albumsiden: titel, beskrivelse, underalbum og billeder
  album.json                      ← albummets oplysninger, så en maskine kan læse dem
  billeder/
    001-vestfacaden.jpg           ← billedet i 1600-2000 px
    002-…
  miniaturer/
    001-vestfacaden.jpg           ← lille kopi til oversigten
  kongekapellet/                  ← et underalbum, med samme opbygning
    index.html
    album.json
    billeder/ …
    miniaturer/ …
```

**Regler:**

1. **Relative adresser.** Alle sider peger på billeder og andre album med relative adresser (`billeder/001-vestfacaden.jpg`, `kongekapellet/index.html`, `../index.html`). Så virker mappen fra en disk, et USB-stik, et webhotel og pakket ud af en zip-fil.
2. **Filnavne efter 5.6** med et løbenummer foran, så rækkefølgen fra Piwigo bevares, fx `001-vestfacaden.jpg`. Har billedet ingen titel, bruges `001.jpg`. Mapperne til underalbum hedder som albummets titel. Piwigos eget filnavn gemmes i `album.json`.
3. **Billedstørrelse.** Billederne hentes i Piwigos egen størrelse **XXL** (ca. 1650 px på den længste side) eller den største størrelse under 2000 px, som galleriet har. Miniaturerne hentes i Piwigos lille størrelse. Billederne skaleres ikke om i browseren. Originalerne hører til sikkerhedskopien, ikke til galleriarkivet.
4. **Billederne ligger som almindelige filer** og ikke som base64 i HTML-filen. Derfor kan et arkiv have tusindvis af billeder.
5. **Siderne virker uden JavaScript.** Hver miniature er et link til billedet, så man altid kan se billederne. JavaScript tilføjer visning i stort format (lightbox) med pile og tastatur, men er ikke nødvendigt. Siderne har temaerne fra afsnit 3 og læsehjælpen fra 5.5: mørk tilstand, skriftstørrelse og op-pil.
6. **Brødkrummer.** Øverst på hver side står vejen til albummet, fx *Roskilde Domkirke › Kongekapellet*, med links op til de overordnede album.
7. **Kun de tekster, der betyder noget.** Med kommer albummets titel og beskrivelse og billedets titel, eventuelle beskrivelse og **fotograf** (Piwigos felt «Forfatter»), når det er udfyldt. Fotografen vises under billedet som «Foto: …». Datoer, nøgleord (tags) og kommentarer fra Piwigo kommer ikke med, fordi de ikke er udfyldt ensartet i galleriet. Teksterne står på siden, så mennesker kan læse dem, og i `album.json`, så de kan flyttes til et andet galleri senere. Piwigos beskrivelser kan indeholde HTML. I `album.json` gemmes de uændret, men på siden vises kun almindelig tekst og links (`http(s)`, efter 5.3), aldrig scripts.
8. **Sedlen.** `index.html` har kildedata efter 5.1 med `"format": "galleriarkiv-album"` og de samme oplysninger som `album.json`. Så kan SFB-afspilleren og søgeindekset læse albummet på samme måde som byggernes filer.
9. **Dublin Core** udfyldes én gang for hovedalbummet og gælder for alle underalbum. Kun title, description, identifier og source er forskellige fra album til album. dc.type er **Collection**, dc.source er albummets adresse i Piwigo, og dc.date er **den dag, arkivet er hentet**, fordi datoerne i galleriet ikke er til at stole på. dc.identifier følger 4.1 med typeordet `galleri`, fx `SFB-galleri-roskilde-domkirke-2026`. Panelet **Om denne fil** hedder her **Om dette arkiv**. Der er ingen knap **Gem filen**, fordi arkivet er en mappe.
10. **Kontroltal.** Hvert billede får et kontroltal (SHA-256) i `album.json`, så man om mange år kan tjekke, at filerne stadig er hele.
11. **Hentning.** Billederne hentes ét ad gangen, så webhotellet ikke belastes, og værktøjet viser, hvor langt det er. Mappen gemmes direkte på computeren, hvor browseren kan det (Chrome, Edge og Vivaldi). Bliver hentningen afbrudt, springes de billeder over, der allerede er hentet, når man starter igen. Andre browsere får i stedet hele arkivet som én zip-fil uden komprimering (5.7, regel 8), højst ca. 4 GB.
12. **Kun det, der er offentligt.** Værktøjet henter som udgangspunkt kun album og billeder, som alle kan se i galleriet. Er man logget ind i Piwigo og vil have private album med, skal man vælge det selv, og så står der *Privat* på albumsiden og i `album.json`.

**`album.json`** (forkortet):

```json
{ "format": "galleriarkiv-album", "builderVersion": 1,
  "titel": "Kongekapellet", "beskrivelse": "…",
  "hentet": "2026-11-…", "piwigo": { "id": 123, "adresse": "https://…/index.php?/category/123" },
  "overordnet": { "titel": "Roskilde Domkirke", "mappe": ".." },
  "underalbum": [ { "titel": "…", "mappe": "…", "antal": 64 } ],
  "billeder": [
    { "fil": "billeder/001-vestfacaden.jpg", "miniature": "miniaturer/001-vestfacaden.jpg",
      "titel": "Vestfacaden", "beskrivelse": "…", "fotograf": "…",
      "bredde": 1656, "hoejde": 1242, "bytes": 412345,
      "sha256": "…", "piwigo": { "id": 4567, "fil": "IMG_2588.jpg" } } ],
  "dublinCore": { … } }
```

**`LAES-MIG.txt`** forklarer kort, i almindelig tekst: Hvad arkivet er, hvor det kommer fra, hvornår det er hentet, og at man åbner `index.html`. Det skal kunne læses, også hvis ingen browser kan vise HTML.

**Afgjort i november 2026:**
- Navnet er **Galleriarkiv**.
- dc.date er den dag, arkivet er hentet.
- Kun titler, beskrivelser og fotograf kommer med, se regel 7.
- SFB-afspilleren skal kunne vise et galleriarkiv som zip-fil. Det kan den fra version 0.9: Hovedalbummet bliver ét kort, og underalbummene åbnes inde fra det. Zip-filen må være lavet med Galleriarkiv eller med fx Finders «Komprimer».

---

## 6. Markdown i tekstfelter

Alle apps understøtter den samme lille delmængde.

| Skrives | Bliver til |
|---|---|
| `**fed**` | fed |
| `*kursiv*` | kursiv |
| `## Overskrift` | `<h2>` |
| `### Overskrift` | `<h3>` |
| `- punkt` | punktliste |
| `1. punkt` | nummereret liste |
| `> citat` | citat med streg i accentfarven |
| `[tekst](https://…)` | link, åbner i nyt faneblad |
| `---` | vandret streg |
| `` `kode` `` | kode med baggrund |

Tekst escapes før oversættelsen. Derfor skal reglerne matche den escapede tekst: et citat genkendes som `&gt;` i starten af linjen, ikke som `>`. Sidens titel er `<h1>`, så et enkelt `#` giver også `<h2>`. Hjælpeteksten viser syntaksen med eksempler.

---

## 7. Indlæsning af eksisterende fil

- Feltet står øverst og hedder **Indlæs eksisterende … (valgfri)**.
- Filen parses med DOMParser, og `builder-source-data` læses. Mangler blokken, afvises filen med: "Filen kan ikke indlæses — den er ikke lavet med denne udgave af værktøjet."
- Har filen et andet `format`, afvises den med: "Filen er lavet med et andet værktøj i serien og kan ikke indlæses her."
- Er `builderVersion` lavere end byggerens egen, afvises filen med samme besked som ved manglende blok. Byggeren indeholder ikke kode til at omsætte gamle formater; filen laves om.
- Billeder og lyd hentes tilbage fra data:-URL'erne.
- Tællerkoden følger med over.
- Udgiverværdier rettes til gældende streng (se 4.1).
- Status kvitterer med filnavnet og nævner, hvis tællerkoden blev fundet.

---

## 8. Billeder

Skaleres til maks. **1600 px** på den længste led og gemmes som JPEG, kvalitet **0,82**. Faste indstillinger, ingen knapper at skrue på. Statuslinjen viser omtrentlig størrelse efter komprimering.

Undtagelse: Enkeltbillede · Bygger gemmer i høj opløsning (3000-4000 px eller originalen) og bevarer Exif, fordi det er appens formål.

Undtagelse: Før og Nu · Bygger lader brugeren vælge **1400, 1800 eller 2200 px** på den længste led (kvalitet 0,85), fordi de to billeder tegnes om i samme pixelmål og skal flugte præcist. 1800 px er forvalgt. Billederne kan derfor ikke gemmes urørt, og Exif følger ikke med.

Undtagelse: Præsentation · Bygger gemmer billederne i **2560 px** og kvalitet **0,85**, fordi de skal vises på storskærm eller projektor. Der kan højst være **25 billeder**, så filen holder sig omkring 15-25 MB. Hvert billede får desuden en lille udgave (480 px, 0,78), som bruges i oversigten, i forhåndsvisningen og som `<link rel="icon">`. Den store udgave ligger i `data-full` på billedet og hentes først, når billedet vises.

Undtagelse: Samling · Bygger gemmer miniaturerne på kortene i **800 px** og kvalitet **0,82**, fordi de kun vises i lille størrelse. Forsidebilledet følger 1600 px / 0,82.

Undtagelse: Mediekort · Bygger gemmer billedet til et **talende billede** i op til **2000 px** på den længste led og kvalitet **0,85**, i billedets egen form (ingen beskæring til 16:9), fordi billedet er hovedsagen og skal kunne ses i fuld skærm. Til video og podcast beskæres plakatbilledet til 16:9 og følger 1600 px / 0,82.

Bemærk: canvas-komprimering fanger kun første billede i en animeret GIF.

Billederne behandles ét ad gangen, så computeren ikke går i stå, når man vælger mange store billeder på én gang. Rækkefølgen følger det valgte.

### 8.1 Oplysninger fra sidecar-filer

Byggere, der tager imod billeder, kan læse oplysninger fra de små filer, som andre systemer lægger ved siden af billederne. Man markerer billeder og sidecar-filer på én gang i samme filvælger.

| Kilde | Filtype | Hentes | Til |
|---|---|---|---|
| Google Takeout, pr. billede | `.json` (også `.supplemental-metadata.json`) | `description`, `photoTakenTime` | billedets beskrivelse, optagelsesdato |
| Google Takeout, albummet | `metadata.json` | `title`, første `narrativeEnrichment.text` | albummets titel og beskrivelse |
| Fotosafari | `.txt`, linjer: fotograf, beskrivelse, bredde, længde, gruppe, kode | fotograf, beskrivelse | dc.creator (flere adskilt af semikolon), billedets beskrivelse |
| Billedarkiv, pr. billede | `.json` med feltet `exportedFilename` | `title`, `description`, `photographer`, `year`, `order`, `album` | billedets titel og beskrivelse, dc.creator (flere adskilt af semikolon), optagelsesdato (1. januar i året), rækkefølgen, albummets titel |

Regler:

- Sidecar og billede kobles på filnavnet uden endelse, uden forskel på store og små bogstaver.
- Et felt udfyldes kun, hvis det er tomt. Det, man selv har skrevet, overskrives aldrig.
- Et usynligt BOM-tegn forrest i en tekstfil fjernes.
- Koordinater, gruppe og kode fra Fotosafari bruges ikke og kommer ikke med i outputfilen.
- En sidecar fra Billedarkiv kendes på feltet `exportedFilename` og kobles på det filnavn. Den må aldrig forveksles med Takeouts `metadata.json`, selv om begge har en `title`. Billedarkivs titel bruges kun, hvis billedet stadig har filnavnet som titel. Står der et årstal i `year` (også fx "ca. 1955"), bliver det til 1. januar det år. Billederne lægges i rækkefølgen fra `order`. Oversigtsfilerne `_album-oversigt.json` og `_album-oversigt.txt` springes over, fordi de blot gentager oplysningerne.
- Er der optagelsesdatoer, vises knappen **Sortér efter optagelsesdato**, og datoen gemmes i kildedata, så sorteringen også virker efter genindlæsning.
- Beskedboksen fortæller, hvor mange billeder der fik oplysninger, eller at ingen filer passede.

---

## 9. Tilgængelighed

- Kontrast mindst 4,5:1 for al tekst, i både lys og mørk tilstand.
- Synligt fokus: 2 px outline i builderen, 3 px på gem-knappen.
- Klikflader mindst 44 × 44 px.
- Ingen information formidlet med farve alene.
- Hvert billede har et felt til alt-tekst. Er det tomt, bruges billedets titel.
- Layoutet virker ned til mobilbredde.
- Sproget er dansk, i hele sætninger, uden fagudtryk. En knap siger hvad der sker.

---

## 10. Tjekliste

**Builderen**
- [ ] Topbar med appnavn, en linje om hvad den gør, og versionsnummer
- [ ] To-rudet layout (1fr 420px) med live forhåndsvisning i iframe
- [ ] Feltrækkefølgen fra afsnit 2
- [ ] Skriftstørrelser og kontrast fra afsnit 3
- [ ] Afkrydsningstekster med almindelige bogstaver, ikke versaler
- [ ] De syv temaer med `THEMES` kopieret uændret fra afsnit 3
- [ ] Advarsel fra browseren, før man forlader siden med ugemte ændringer
- [ ] Indlæsning af egne filer med pæn afvisning af fremmede filer, andre formater og ældre builderVersion
- [ ] Billeder 1600 px / 0,82 (undtagelserne står i afsnit 8)
- [ ] Alt-tekstfelt pr. billede
- [ ] Sidecar-filer læses efter 8.1 (hvor appen tager imod billeder)

**Dublin Core**
- [ ] Alle 15 felter, i rækkefølge, med samme labels
- [ ] Hjælpetekster starter med feltnavnet
- [ ] dc.type-listen fra afsnit 4 med appens egen type forvalgt
- [ ] Automatisk udfyldning af title, description, coverage og identifier med dcTouched-flag
- [ ] `PUBLISHERS` kopieret uændret fra 4.1, vist som rulleliste
- [ ] Gamle udgiverværdier rettes ved indlæsning
- [ ] Udgiveren står som første emneord
- [ ] Identifikator foreslås som `PRÆFIKS-type-titelslug-år`
- [ ] Knap til JSON-eksport af metadata

**Outputfilen**
- [ ] DC-meta i `<head>` og "Om denne fil"-panel
- [ ] `builder-source-data` med format, builderVersion og theme
- [ ] Temaet som CSS-variabler i `:root`, med mørk tilstand
- [ ] Hjem-knap med Automatisk / Altid / Aldrig, og `?hjem=` i adressen går forud
- [ ] `?hjem=` accepterer kun relative og `http(s)`-adresser (5.3)
- [ ] "Gem filen" gemmer en ren kopi
- [ ] "Gem filen" og "Om denne fil" kan slås fra
- [ ] Skriftstørrelse og op-pil (på lange sider)
- [ ] Tællerkode bevares ved genindlæsning
- [ ] og:-tags og felt til delingsbillede
- [ ] Markdown-delmængden fra afsnit 6, også citat
- [ ] Filnavn som slug af titlen med æ→ae, ø→oe, å→aa (5.6)
- [ ] "Gem filen" i pilleform med kursiv linje under

---

## 11. AI-assisteret udfyldning af Dublin Core

### 11.1 Formål

Når flere foreninger og enkeltpersoner leverer materiale, skal metadata udfyldes ens. En AI (fx et Claude-projekt med prompten nedenfor som instruktion) læser teksten og foreslår de 15 felter. Et menneske godkender altid resultatet, før det sættes ind i en bygger.

### 11.2 Arbejdsgang

Arbejdsgangen foregår i **Metadata-hjælper**, som laver INDLEDNINGEN ud fra en formular og kontrollerer AI'ens svar. Den kan også gøres i hånden.

1. Bidragyderen udfylder INDLEDNING (i Metadata-hjælperens formular eller øverst i sin tekst).
2. Prompten med INDLEDNING og tekst indsættes i AI'en, som svarer med de 15 felter.
3. Svaret kontrolleres. Metadata-hjælper retter kendte stavemåder af udgiveren og markerer felter med [?], forkert dato, forkert type, udfyldt identifikator, udgiver der ikke står som første emneord, navne der ikke findes i teksten, og felter der afviger fra INDLEDNINGEN. Felterne kan ikke kopieres videre, før fejl og [?] er rettet.
4. Identifikatoren overlades til byggeren, der foreslår den automatisk.
5. Felterne kopieres ind i byggeren.

Metadata-hjælper kan læse svaret, uanset om AI'en skriver ét felt pr. linje, alle felter i ét afsnit, en tabel eller med fed skrift. Afprøvet med Claude, Gemini og Copilot 365.

### 11.3 Indledning (udfyldes af bidragyder)

```
INDLEDNING
Navn (ophav):
Øvrige bidragydere:
Dato for materialet:
Udgiver:
Rettigheder:
Kilde (hvor stammer materialet fra):
Arbejdstitel:
Type:
Sted og periode:

TEKST
...
```

De tre sidste linjer er valgfrie. Type skrives som dc.type-værdien (fx Sound). I Metadata-hjælper udfyldes den ud fra den valgte bygger.

### 11.4 Prompt

```
Du udfylder Dublin Core-metadata ud fra en tekst med en INDLEDNING.
Svar KUN med de 15 felter nedenfor, i denne rækkefølge, én linje
pr. felt. Ingen indledning, ingen kommentarer, ingen forklaringer.

REGLER
- Brug oplysninger fra INDLEDNING præcis som de står.
- Gæt aldrig på navne, datoer eller rettigheder. Mangler de,
  lad feltet stå tomt.
- Er du i tvivl om et forslag, skriv [?] efter det.
- Dato: helst ÅÅÅÅ-MM-DD. ÅÅÅÅ-MM, ÅÅÅÅ eller "ca. ÅÅÅÅ" er også i orden.
- Udgiver: KUN én af disse, stavet præcis sådan:
  cirkler
  Historisk Samfund for Roskilde Amt
  Privat
  Roskilde Musik
  Roskilde TV
  Røde Kors
  Syd for Banen - Lokalhistorisk Forening
  Passer ingen, lad feltet stå tomt.
- Emne: udgiveren som første emneord, derefter 3-6 emneord
  adskilt af semikolon.
- Beskrivelse: 2-4 saglige sætninger uden vurderinger.
- Type: Text, Image, Sound, MovingImage, InteractiveResource,
  Collection eller Event.
- Format: text/html.
- Identifikator: lad altid stå tom.
- Sprog: da, medmindre teksten er på et andet sprog.
- Dækning: sted og/eller periode som teksten handler om.

FELTER
Titel (dc.title):
Ophav / skaber (dc.creator):
Emne / nøgleord (dc.subject):
Beskrivelse (dc.description):
Udgiver / område (dc.publisher):
Bidragyder (dc.contributor):
Dato (dc.date):
Type (dc.type):
Format (dc.format):
Identifikator (dc.identifier):
Kilde (dc.source):
Sprog (dc.language):
Relation (dc.relation):
Dækning (dc.coverage):
Rettigheder (dc.rights):
```

### 11.5 Vedligeholdelse

Udgiverlisten i prompten skal altid svare til `PUBLISHERS` i 4.1. Kommer der en ny udgiver, rettes den tre steder: i `PUBLISHERS` i byggerne, i prompten her og i Metadata-hjælper (som bygger prompten ud fra sin egen kopi af `PUBLISHERS`).

Ændres prompten her, rettes `PROMPT` i Metadata-hjælper tilsvarende.

---

## 12. Ændringslog

**Version 2.21 — november 2026.**
- Afsnit 5.8: SFB-afspilleren (v0.9) viser et galleriarkiv fra en zip-fil. Kun hovedalbummet bliver et kort; sider med `overordnet` springes over, også når zip-filen er lavet uden den øverste mappe. Prøven med Roskilde Domkirke er gennemført.
- `CLAUDE.md` nævner Galleriarkiv som et værktøj, der skal ligge på galleriets adresse.

**Version 2.20 — november 2026.**
- Nyt værktøj: **Galleriarkiv** (`tools/galleriarkiv.html`, v1.0, format `galleriarkiv-album`, builderVersion 1) følger afsnit 5.8. Det finder albummet og alle underalbum i Piwigo, henter billederne i Piwigos XXL-størrelse og miniaturerne ét ad gangen og skriver albumsider, `album.json` og `LAES-MIG.txt`. Er man logget ind i galleriet uden at have valgt private album, stopper værktøjet, så private billeder ikke kommer med ved et uheld. Beskrivelser fra Piwigo vises kun som tekst og links til http(s).
- Afsnit 5.8, regel 11: Browsere, der ikke kan gemme en mappe, får hele arkivet som én zip-fil i stedet for én pr. album, så arkivet kan pakkes ud i ét stykke.

**Version 2.19 — november 2026.**
- Nyt afsnit 5.8 **Galleriarkiv**: Et helt album fra Piwigo med underalbum gemmes som en mappe med HTML-sider, billeder i 1600-2000 px, miniaturer, `album.json` og `LAES-MIG.txt`, så galleriet kan leve videre uden PHP og database. Kun titler, beskrivelser og fotograf kommer med, og dc.date er hentedatoen. Afsnittet beskriver også de tre lag: sikkerhedskopi af Piwigo (FTP og database), galleriarkiv og online-galleri. Værktøjet er ikke lavet endnu.

**Version 2.18 — november 2026.**
- Ny bygger: **Podcastserie · Bygger** (`podcastserie-bygger.html`, v1.0, format `podcastserie`, builderVersion 1). Den laver én side med en afspilningsliste til en serie på 2-6 lydafsnit. Hvert afsnit har titel, beskrivelse, eget billede (16:9, højst 1200 px), en lydfil i mappen `media/` og en reserveadresse på nettet. Lydfilerne hedder som serien med løbenummer, fx `…-01.mp3`, og har kontroltal (5.7). Afspilleren går selv videre til næste afsnit og husker, hvor lytteren nåede til. Afsnit kan hentes fra færdige mediekort med lyd: Lyd, der ligger inde i et mediekort, bliver til en lydfil i mappen, og kortets adresse bliver reserve. dc.type er Sound, og identifikatoren får ordet `serie`.

**Version 2.17 — november 2026.**
- Afsnit 5.7, regel 6: Byggeren beregner altid et kontroltal (SHA-256) for mediefilerne i mappen og skriver det i listen `media` som `sha256`. Det beregnes i bidder, fordi browserens indbyggede beregning kræver hele filen i hukommelsen. Vælges filen igen til et indlæst kort, siger byggeren, om det er præcis den samme fil.
- Mediekort · Bygger (v2.0) følger reglen. Zip-filens eget kontroltal beregnes i samme omgang, så filen kun læses én gang.

**Version 2.16 — november 2026.**
- Mediekort · Bygger (v1.8) har knappen **Gem hele mappen som zip-fil**: Mappen med kortet og videofilen, med de rigtige navne, gemmes som én zip-fil uden komprimering (5.7, regel 8). Videofilen læses kun én gang for at beregne zip-formatets kontroltal og kopieres ikke ind i hukommelsen. En zip-fil kan højst være ca. 4 GB.
- Mediekort 1.7 er prøvet af med en rigtig film fra pCloud: Fra mappen afspilles den uden internet, og uden mappen bruges reserven.

**Version 2.15 — november 2026.**
- Mediekort · Bygger (v1.7) følger afsnit 5.7: Valget **Videofil** kan pege på en videofil i mappen `media/` ved siden af kortet og have en adresse på nettet som reserve. Byggeren viser, hvordan mappen skal se ud, og advarer, når browseren ikke kan afspille filen. Kortet siger selv, hvilken fil der mangler. Filformatet er udvidet med listen `media`, så `builderVersion` er stadig 1.

**Version 2.14 — november 2026.**
- Nyt afsnit 5.7 **Mappeformat**: Video og store lydfiler lægges i en mappe ved siden af HTML-filen med relative adresser, faste formater (MP4 med H.264/AAC og MP3), en reserveadresse online og en liste over mediefilerne i sedlen. HTML-filen skal kunne stå alene og sige, hvad der mangler. Grundprincip 1 nævner undtagelsen.
- Ingen bygger er ændret endnu. Mediekort · Bygger tages op først (se afsnit 13).

**Version 2.13 — oktober 2026.**
- Alle byggere advarer nu, før man forlader siden med ugemte ændringer: Har man skrevet eller lagt noget ind, siden filen sidst blev gemt som .html, spørger browseren, om man vil forlade siden. At indlæse en fil tæller ikke som en ændring. Ny linje i tjeklisten (afsnit 10). Byggerne har fået et nyt versionsnummer; filformaterne er uændrede.

**Version 2.12 — oktober 2026.**
- Afsnit 1: **Lokalhistorier · fri udgave** i mappen `fri/` med undtagelserne for udgiver, temaer, emneord og identifikator. Den laves af opskriften `fri/lav-fri-udgave.py`.

**Version 2.11 — oktober 2026.**
- Søgeside · Bygger (v1.4) og Søgeindeks · Online: Når man er rullet langt ned i listen, kommer knappen **Til søgningen** frem nederst til højre. Den fører op til toppen og sætter markøren i søgefeltet.

**Version 2.10 — oktober 2026.**
- Nyt tema i afsnit 3: **cirkler** (`cirkler`) med farver fra fællesskabets logo: dyb lilla accent, gul tekst på accenten og creme baggrund. Farveprikken i vælgeren er tre små cirkler. Temaet er tilføjet i alle 11 byggere.
- Mediekort · Bygger (v1.3) viser cirklers tre cirkler i kortet og på den tegnede plakat, når temaet er valgt.
- Filformatet er uændret.

**Version 2.9 — oktober 2026.**
- Ny udgiver i afsnit 4.1: **cirkler** med præfikset `CIR`, fx `CIR-side-…-2026`. Navnet staves med små bogstaver, som fællesskabet selv gør. Den er tilføjet i `PUBLISHERS` i alle byggere og i Metadata-hjælper og i prompten i afsnit 11.
- Søgeindeks · Online: cirkler står i `foreninger.json` med nøglen `cirkler`, så `?forening=cirkler` viser deres materiale.

**Version 2.8 — oktober 2026.**
- Projektet handler nu om **lokalhistorier**: det, der foregår lige uden for døren, så alle slags foreninger passer ind. Søgeside · Bygger er nu v1.3 med standardtitlen "Søg i lokalhistorier", og søgeportalens forside og titler er rettet til. Byggerne er ikke ændret, ud over to eksempeltekster i Bogbygger (4.1) og Enkeltbillede (3.1).

**Version 2.7 — oktober 2026.**
- Ny fil `emneord.md` med foreningernes fælles emneord, fx Musik og Arkitektur. Afsnit 4 henviser til den under dc.subject. Udgiveren siger, hvem der har lavet en historie, og de fælles emneord siger, hvad den handler om, så man kan finde alt om et emne på tværs af foreningerne.

**Version 2.6 — oktober 2026.**
- Nyt tema i afsnit 3: **Roskilde Musik** (`roskildemusik`) med farver fra foreningens logo. Accenten er en brændt orange (`#C2410C`), så hvid tekst på den er let at læse. Farveprikken i vælgeren er en lille plade i logoets klare orange og sort. Temaet er tilføjet i alle 11 byggere.
- Filformatet er uændret, så eksisterende filer virker som før.

**Version 2.5 — oktober 2026.**
- Ny udgiver i afsnit 4.1: **Roskilde Musik** med præfikset `RMU`, fx `RMU-lyd-interview-med-…-2026`. Den er tilføjet i `PUBLISHERS` i alle byggere og i Metadata-hjælper og i prompten i afsnit 11.
- Søgeindeks · Online: Roskilde Musik står i `foreninger.json` med nøglen `rmu`, så `?forening=rmu` viser deres materiale. Logo, hjemmeside og rettighedstekst udfyldes, når foreningen har bestemt dem.

**Version 2.4 — oktober 2026.**
- Før og Nu · Bygger (v2.0, `foer-og-nu-bygger.html`, tidligere `foer-og-nubygger.html`) ligger nu i repoet og er bragt i overensstemmelse med standarden. Den var bygget efter 1.0 og fik: de fem temaer, udgiverlisten med første emneord og automatisk identifikator (`PRÆFIKS-foernu-titelslug-år`), Dublin Core-labels og typer som i afsnit 4, Hjem-knap med kontrol af `?hjem=`, valg af "Gem filen" og "Om denne fil", mørk tilstand, et lille ikon og standardens slug. Filformatet er nu `builderVersion` 2; filer fra den gamle udgave laves om.
- Afsnit 8: undtagelse for Før og Nu · Bygger, som lader brugeren vælge 1400, 1800 eller 2200 px.

**Version 2.3 — oktober 2026.**
- Afsnit 8.1: Billedarkiv er kommet til som kilde. Hver sidecar pr. billede (`.json` med `exportedFilename`) giver titel, beskrivelse, fotograf, årstal, rækkefølge og albummets titel. Oversigtsfilerne `_album-oversigt.*` springes over.
- Album · Bygger v3.1 læser Billedarkivs sidecar-filer. Før blev de taget for Takeouts albumfil, så albummets titel blev sat til titlen på det sidste billede. Rettet. Filformatet (`builderVersion` 3) er uændret.

**Version 2.2 — oktober 2026.**
- Præsentation · Bygger (v1.0, `praesentation-bygger.html`) ligger nu i repoet og er gennemgået mod standarden. Den fik de fulde Dublin Core-labels i panelet Om denne fil og kontrollen af `?hjem=` (5.3), som den manglede. Den følger nu 2.1. Resten stemte: temaer, udgivere, felter, billedindstillingerne i afsnit 8 (2560 px, 25 billeder, lille udgave som ikon) og indlæsning.
- Kontrollen af `?hjem=` fra 2.1 gælder nu alle ti byggere.

**Version 2.1 — oktober 2026.**
- Afsnit 5.3 og tjeklisten: `?hjem=` skal kontrolleres. Kun relative adresser og `http(s)` er tilladt; `javascript:`, `data:` og lignende ignoreres. Før blev adressen sat direkte på Hjem-knappen. Rettet i alle ni byggere. Allerede færdige filer har den gamle kode og skal laves om for at få rettelsen.
- Afsnit 1: Søgeside · Bygger (v1.2) og Søgeindeks · Online er beskrevet som værktøjer og skriver, hvilke dele af standarden de følger.
- Søgeside · Bygger v1.2 og Søgeindeks · Online: henvisninger vises med 🔗 efter deres `link` (ikke efter `dc.type`), kun `http(s)`-adresser bliver til links, og valget uden udgiver hedder **Uden område**.
- Søgeindeks · Online: udgiveren for Røde Kors i `foreninger.json` er rettet til `Røde Kors`, som i `PUBLISHERS`. `config.php` og driftsdata er fjernet fra repoet, og `config-eksempel.php` er kommet til. Den ubrugte kopi `_faelles.php` er slettet.

**Version 2.0 — oktober 2026.** Indholdet er som i 1.9; det er filnavnene, der er ensrettet.
- Alle byggere hedder nu `navn-bygger.html`: `side-bygger.html` (før `sidebygger-hs.html`), `album-bygger.html` (før `albumbygger-dc-v3.html`), `bog-bygger.html` (før `bogbygger.html`), `enkeltbillede-bygger.html` (før `enkeltbillede-dc-v3.html`), `samling-bygger.html` (før `samlingsbygger.html`) og `piwigo-album-bygger.html` (før `piwigo-albumbygger.html`). `henvisning-bygger.html`, `lydfortaelling-bygger.html` og `mediekort-bygger.html` hed allerede sådan.
- Afsnit 1.2: filnavnsreglen er skrevet ind. Versionsnummer hører ikke til i filnavnet.
- Ældre poster i denne log nævner de gamle filnavne, som de var på det tidspunkt.

**Version 1.9 — oktober 2026.**
- Bogbygger (v4.0, `bogbygger.html`, tidligere `bogbygger-v4.html`) og Lydfortælling · Bygger (v4.0, `lydfortaelling-bygger.html`, tidligere `lyd-fort-gen-dc-v4.html`) er bragt i overensstemmelse med standarden og står i listen over byggere, der følger den.
- Enkeltbillede · Bygger (v3.0, `enkeltbillede-dc-v3.html`) er gennemgået og står nu i listen over byggere, der følger standarden. Den viser de fulde Dublin Core-labels i panelet Om denne fil. Dens ikon er en lille udgave på 640 px, som også bruges til forhåndsvisningen, og det er i orden efter afsnit 5.2.
- Afsnit 1: Lydfortælling · Bygger består som den enkle udgave ved siden af Mediekort · Bygger.
- Bogbygger og Lydfortælling · Bygger laver nu et lille ikon til `<link rel="icon">`, som i afsnit 5.2, så forsiden og billedet ikke ligger to gange i filen. Lydfortælling · Bygger viser desuden de fulde Dublin Core-labels i panelet Om denne fil.

**Version 1.8 — oktober 2026.**
- Henvisning · Bygger (v2.0, `henvisning-bygger.html`, tidligere `henvisning-gen-dc-v2.html`) er bragt i overensstemmelse med standarden og står i listen over byggere, der følger den.
- Afsnit 1: dc.type for Henvisning er afgjort. Byggeren forvælger Text, og man vælger selv typen ud fra det, der henvises til. Det åbne punkt i afsnit 13 gælder nu kun Vejledning og PWA.
- Afsnit 4.1: typeordet `henvisning` til dc.identifier, fx `HSR-henvisning-foreningens-hjemmeside-2026`.

**Version 1.7 — oktober 2026.**
- Ny bygger i oversigten: Piwigo Albumbygger (v2.0, `piwigo-albumbygger.html`), der laver et billedalbum ud fra et album i et Piwigo-galleri. dc.type er Image, og identifikatoren bruger typeordet `album`.
- Afsnit 1: beskrevet, at Piwigo Albumbygger bevidst skal ligge på galleriets webadresse og derfor afviger fra grundprincippet om, at builderen kører uden server. Outputfilen er uændret en selvstændig fil.
- Piwigo Albumbygger, Sidebygger, Mediekort · Bygger, Samling · Bygger, Album · Bygger og Enkeltbillede · Bygger viser nu de fulde Dublin Core-labels (Ophav / skaber, Emne / nøgleord, Udgiver / område) i panelet Om denne fil. Album · Bygger og Sidebygger laver nu et lille ikon til `<link rel="icon">`, som i afsnit 5.2.

**Version 1.6 — oktober 2026.**
- Mediekort · Bygger v1.2 har fået typen **Talende billede**: ét billede i god kvalitet med en kort, indtalt fortælling (30 sek. – 3 min.), fuld skærm-visning og dc.type Sound. Den Talende Booklet er udgået.
- Mediekort · Bygger v1.2 er bragt i overensstemmelse med standarden. builderVersion er stadig 1, fordi ændringerne er tilføjelser; tidligere mediekort kan indlæses.
- Afsnit 8: undtagelse for talende billede med 2000 px / 0,85 i billedets egen form.
- Afsnit 4.1: typeordene `podcast` og `talende` til dc.identifier. Det åbne punkt om Mediekort · Byggerens typeord er afgjort.
- Afsnit 5.2: `<link rel="icon">` laves helst som et lille ikon, så billedet ikke ligger i filen to gange.

**Version 1.5 — oktober 2026.**
- Ny bygger i oversigten: Samling · Bygger (v2.0), der laver en forside til 2-6 færdige filer. dc.type er Collection.
- Afsnit 4.1: typeordet `samling` til dc.identifier, fx `SFB-samling-masser-at-kigge-paa-2026`.
- Afsnit 5.3: beskrevet hvordan en samling bruger `?hjem=`, så hjem-knappen i filerne fører tilbage til samlingen.
- Afsnit 8: undtagelse for Samling · Byggerens miniaturer (800 px / 0,82).
- Afsnit 3 og tjeklisten: afkrydsningstekster skrives med almindelige bogstaver. Fejlen, hvor de blev vist med versaler, er rettet i Sidebygger 1.5, som også bruger `--rust` på afkrydsningsfelterne.

**Version 1.4 — oktober 2026.**
- Ny bygger: Præsentation · Bygger (v1.0), der afløser den PHP-baserede Billedpresent. Laver et billedshow til foredrag, som kan vises uden internet.
- Afsnit 8: undtagelse for Præsentation · Bygger med 2560 px / 0,85, højst 25 billeder og en lille udgave til oversigten.
- Afsnit 4.1: typeordet `praesentation` til dc.identifier, fx `SFB-praesentation-de-glemte-butikker-2026`.

**Version 1.3 — oktober 2026.**
- Videokort · Bygger er afløst af Mediekort · Bygger, som laver kort til både video (MovingImage) og podcast (Sound).
- Nyt værktøj: Metadata-hjælper (v1.1), der laver INDLEDNING og prompt efter afsnit 11 og kontrollerer AI'ens svar. Afviger bevidst fra standarden, fordi den ikke laver en fil.
- INDLEDNING i 11.3 udvidet med Arbejdstitel, Type og Sted og periode. Prompten i 11.4 er uændret.
- 11.2 beskriver arbejdsgangen med Metadata-hjælper.
- 11.5: udgiverlisten rettes nu også i Metadata-hjælper.

**Version 1.2 — september 2026.**
- Slug: æ→ae, ø→oe, å→aa, før accenterne fjernes. Gælder filnavn og titelslug i dc.identifier. Gamle filer beholder deres navn.
- "Gem filen": pilleform (999px) og kursiv linje under knappen er nu fastlagt. Rettet i Sidebygger 1.4.
- Temavælgeren viser "Lokalhistorisk", som i referencen. `THEMES` er uændret.
- Markdown: reglerne skal matche den escapede tekst. Citat-fejlen i Sidebyggeren er rettet i 1.4.
- Indlæsning: filer med andet format eller lavere builderVersion afvises.
- Forhåndsvisning: kort forsinkelse og loft over billeder er tilladt i billedtunge apps.
- Nyt afsnit 8.1 om sidecar-filer fra Google Takeout og Fotosafari.
- Alt-tekstfelt pr. billede (afsnit 9).
- Album · Bygger v3.0 er bragt i overensstemmelse med standarden.
- Sidebygger v1.4 følger 1.2 og er fortsat referenceimplementering. Den har desuden fået felt til delingsbillede og `<link rel="icon">`, som manglede efter 5.2.

**Version 1.1 — september 2026.**
- Ny referenceimplementering: Sidebygger (`sidebygger-hs.html`, v1.3) i stedet for Lydfortælling · Bygger.
- Layout 1fr 420px / 900 px / 660 px, som i referencen.
- Fem temaer med lys og mørk udgave, defineret i ét `THEMES`-objekt. Nyt: Historisk Samfund og Roskilde TV.
- Fem faste udgivere i ét `PUBLISHERS`-array med præfikser. "Røde Kors Roskilde" er erstattet af "Røde Kors". Almindelig bindestreg.
- Områdetillægget fra v1.0 er indarbejdet i afsnit 4.1.
- Identifikator foreslås automatisk (`PRÆFIKS-type-titelslug-år`) i stedet for løbenummer.
- dc.type-listen udvidet med MovingImage.
- Hjælpetekster til DC må tilpasses appen, når de starter med feltnavnet.
- Hjem-knap med Automatisk / Altid / Aldrig.
- "Gem filen" og "Om denne fil" kan slås fra. "Gem filen" gemmer en ren kopi.
- Mørk tilstand, skriftstørrelse og op-pil i outputfilen.
- Billeder 1600 px / 0,82 i stedet for 1100 px / 0,78.
- Filer lavet før ensretningen laves om. Ingen kode til gamle formater.
- Nyt afsnit 11 om AI-assisteret udfyldning af Dublin Core.

**Version 1.0 — august 2026.** Første udgave, skrevet ud fra Lydfortælling · Bygger version 2.

---

## 13. Åbne punkter

**Mappeformatet (5.7), i denne rækkefølge:**
1. ~~Mediekort · Bygger: valget "Videofil" får en lokal fil i mappen plus en reserveadresse, beskeden når filen mangler, og listen `media` i kildedata.~~ Lavet i Mediekort 1.7 (version 2.15) og prøvet af med en rigtig film fra pCloud i november 2026.
2. ~~Byggeren laver hele mappen som en zip-fil uden komprimering, så brugeren ikke selv skal flytte filer på plads.~~ Lavet i Mediekort 1.8 (version 2.16). Højst ca. 4 GB, fordi zip-filen laves uden ZIP64.
3. ~~Kontroltal (SHA-256) for store filer.~~ Lavet i Mediekort 2.0 (version 2.17): beregnes i bidder, når filen vælges, og tjekkes, når filen vælges igen til et indlæst kort.
4. ~~En serie med flere lydfiler, fx en podcastserie, som en samling med afspilningsliste.~~ Lavet som Podcastserie · Bygger 1.0 (version 2.18).
5. Søgeportalen tager imod en hel mappe som zip-fil og pakker den ud. Kræver kun tilladte filtyper (mp4, mp3 og billeder), beskyttelse mod filer uden for mappen og en løsning på webhotellets grænse for upload.

**Galleriarkivet (5.8), i denne rækkefølge:**
1. ~~Forslaget drøftes, og de åbne spørgsmål afgøres.~~ Afgjort i november 2026 (se 5.8).
2. ~~Værktøjet laves og prøves på Roskilde Domkirke (`index.php?/category/48`, hovedalbum med 8 underalbum, 572 billeder).~~ `tools/galleriarkiv.html` er lavet (v1.0, version 2.20). Domkirken med alle underalbum blev hentet uden problemer i november 2026 over en 5G-forbindelse, og arkivet ser fint ud fra disken.
3. SFB-afspilleren viser et galleriarkiv fra version 0.9. Tilbage står at afgøre, om flere album skal med.

**Afklares, når byggeren tages op:**
- dc.type for Vejledning og PWA.
- Hvilke af byggerne der skal have skriftstørrelse og op-pil (korte outputfiler kan undvære dem). Afgjort: Sidebygger, Album · Bygger og Præsentation · Bygger har begge dele.
