# Oversigt over byggere og værktøjer

Status: 18. oktober 2026 · Builder-standard version 2.8 (`builder-standard.md`)

Versionsnummeret står i byggerens eller værktøjets topbar (eller, hvor der ikke er en topbar, i en kommentar øverst i filen). **Filformat** er det tal, en bygger skriver i sine færdige filer som `builderVersion`. Det tælles op, når formatet ændres, og en bygger afviser filer med et lavere tal end sit eget.

## Byggere

Byggerne laver en færdig fil, som kan gemmes, deles og åbnes uden internet. De følger Builder-standarden.

| Navn | Fil | Version | Filformat | Laver | dc.type |
|---|---|---|---|---|---|
| Sidebygger | `side-bygger.html` | 1.5 | 1 | artikelside om gader, steder og mennesker | Text |
| Album · Bygger | `album-bygger.html` | 3.1 | 3 | billedalbum med grid og lightbox | Image |
| Enkeltbillede · Bygger | `enkeltbillede-bygger.html` | 3.1 | 3 | ét billede i høj kvalitet med tekst og Exif | Image |
| Før og Nu · Bygger | `foer-og-nu-bygger.html` | 2.0 | 2 | to billeder fra samme sted, rettet ind oven på hinanden, med skyder eller overtoning | Image |
| Bogbygger | `bog-bygger.html` | 4.1 | 4 | e-bog med forside og kapitler | Text |
| Lydfortælling · Bygger | `lydfortaelling-bygger.html` | 4.0 | 4 | lydafspiller med billede og tekst | Sound |
| Mediekort · Bygger | `mediekort-bygger.html` | 1.2 | 1 | kort til en video, en podcast eller et talende billede | MovingImage eller Sound |
| Præsentation · Bygger | `praesentation-bygger.html` | 1.0 | 1 | billedshow til foredrag og oplæg, med pile, fuld skærm og automatisk visning | Image |
| Henvisning · Bygger | `henvisning-bygger.html` | 2.0 | 2 | "skilt" der sender videre til en side på nettet | Text (kan ændres) |
| Samling · Bygger | `samling-bygger.html` | 2.0 | 2 | forside der binder 2–6 færdige filer sammen | Collection |
| Piwigo Albumbygger | `piwigo-album-bygger.html` | 2.0 | 1 | billedalbum hentet fra et album i et Piwigo-galleri | Image |

**Piwigo Albumbygger** skal ligge på galleriets egen webadresse for at kunne hente fra Piwigo. De andre kører lokalt.

## Værktøjer

Værktøjerne ligger i mappen `tools/`. De følger ikke Builder-standarden, men er hver især én fil, der virker uden internet.

| Navn | Fil | Version | Gør |
|---|---|---|---|
| Søgeside · Bygger | `tools/soegeside-bygger.html` | 1.3 | laver en søgeside ud af en mappe med færdige filer |
| Metadata-hjælper | `tools/metadata-hjaelper.html` | 1.2 | laver en AI-prompt til Dublin Core og kontrollerer svaret |
| Feltoptager | `tools/feltoptager.html` | 1 | optager lyd, rydder op og gemmer som mp3 eller som lydfortælling (HTML) |
| Lydlab | `tools/lydlab.html` | 1 | gør en lydoptagelse klar: mono, jævn lyd og fast lydstyrke, ud kommer en mp3 |
| Fotolab | `tools/fotolab.html` | 4 | farve-effekter, billedformat og Polaroid med håndskrevet tekst |
| Musikpakke · Bygger | `tools/musikpakke-bygger.html` | 1 | laver en offline musik-app (PWA) med 3–5 samlinger som zip-fil |
| Skyggeramme | `tools/skyggeramme.html` | – | sætter en blød skygge om et skærmbillede til wikien |
| Kodeord og hash | `tools/password-hash.html` | 2.0 | laver et sikkert kodeord og den linje med bcrypt-hash, der skal i `config.php` |

**Billedarkiv** ligger i sin egen mappe, fordi det er et helt system med sin egen datafil og vejledning:

| Navn | Fil | Version | Gør |
|---|---|---|---|
| Billedarkiv | `billedarkiv/billedarkiv.html` | 1.1 | ordner billeder i albums med titel, beskrivelse, fotograf, årstal og rækkefølge, og eksporterer et album som zip-fil til Album · Bygger. Kræver Chrome eller Edge. Se `billedarkiv/README.md` |

`–` betyder, at der ikke står noget versionsnummer i filen. Adgangskode-hashes til `sbtv/config.php` laver du med **Kodeord og hash** (`tools/password-hash.html`) på din egen computer eller med `sbtv/lav-hash.php` på serveren. De giver den samme slags hash.

Byggere og værktøjer er hver én fil og henter ikke kode eller skrifttyper fra fremmede steder. De kan bruges uden internet. Undtagelserne er det, de selv skal hente eller vise: Piwigo Albumbygger henter fra galleriet, og Mediekort viser YouTube-videoer.

## Andet

| Navn | Fil | Hvad det er |
|---|---|---|
| Dashboard | `bygger-dash.html` | forside med links til byggere og værktøjer |
| Søgeindeks · Online | `sbtv/` | den samme søgeside som Søgeside · Bygger, men med login, upload og administration på et webhotel med PHP. Se `sbtv/LAES-MIG.md` |
| Builder-standarden | `builder-standard.md` | fælles regler for byggerne, version 2.8 |
| Projektresumé | `projekt-resume.md` | kort fortælling om hele projektet til idéudvikling og sparring med mennesker og Claude |
| Fælles emneord | `emneord.md` | emneord, som alle foreninger staver ens, fx Musik og Arkitektur, så man kan finde et emne på tværs af foreningerne |
| Husregler | `CLAUDE.md` | regler for samarbejdet med Claude |

## Det hænger sammen

- **Feltoptager → Lydfortælling · Bygger.** Feltoptageren gemmer lydfortællinger i Lydfortælling · Byggerens filformat (nu 4). Tælles filformatet op i byggeren, skal Feltoptageren rettes med, ellers kan byggeren ikke indlæse filerne.
- **Byggerne → Søgeside · Bygger og Søgeindeks · Online.** Søgesiderne læser Dublin Core-oplysningerne i de færdige filer.
- **Samling · Bygger** samler færdige filer fra de andre byggere.
- **Billedarkiv, Google Takeout og Fotosafari → Album · Bygger.** Album · Bygger læser deres sidecar-filer, når man vælger billederne og sidecar-filerne på én gang (standardens afsnit 8.1). Billedarkiv ligger i `billedarkiv/`. Fotosafari er dit eget værktøj og ligger ikke i repoet.

## Nævnt i standarden, men ikke i repoet endnu

Vejledningsbygger og PWA · Bygger. Standarden beskriver dem, men filerne ligger ikke her.

## Filnavne

Byggere hedder `navn-bygger.html`. Værktøjer hedder det enkleste, der passer (`fotolab.html`), også uden versionsnummer i navnet, for adressen skal være den samme fra version til version. Små bogstaver, bindestreg mellem ordene og ingen æ, ø og å.
