# Oversigt over byggere og værktøjer

Status: 18. oktober 2026 · Builder-standard version 2.1 (`builder-standard.md`)

Versionsnummeret står i byggerens eller værktøjets topbar (eller, hvor der ikke er en topbar, i en kommentar øverst i filen). **Filformat** er det tal, en bygger skriver i sine færdige filer som `builderVersion`. Det tælles op, når formatet ændres, og en bygger afviser filer med et lavere tal end sit eget.

## Byggere

Byggerne laver en færdig fil, som kan gemmes, deles og åbnes uden internet. De følger Builder-standarden.

| Navn | Fil | Version | Filformat | Laver | dc.type |
|---|---|---|---|---|---|
| Sidebygger | `side-bygger.html` | 1.5 | 1 | artikelside om gader, steder og mennesker | Text |
| Album · Bygger | `album-bygger.html` | 3.0 | 3 | billedalbum med grid og lightbox | Image |
| Enkeltbillede · Bygger | `enkeltbillede-bygger.html` | 3.0 | 3 | ét billede i høj kvalitet med tekst og Exif | Image |
| Bogbygger | `bog-bygger.html` | 4.0 | 4 | e-bog med forside og kapitler | Text |
| Lydfortælling · Bygger | `lydfortaelling-bygger.html` | 4.0 | 4 | lydafspiller med billede og tekst | Sound |
| Mediekort · Bygger | `mediekort-bygger.html` | 1.2 | 1 | kort til en video, en podcast eller et talende billede | MovingImage eller Sound |
| Henvisning · Bygger | `henvisning-bygger.html` | 2.0 | 2 | "skilt" der sender videre til en side på nettet | Text (kan ændres) |
| Samling · Bygger | `samling-bygger.html` | 2.0 | 2 | forside der binder 2–6 færdige filer sammen | Collection |
| Piwigo Albumbygger | `piwigo-album-bygger.html` | 2.0 | 1 | billedalbum hentet fra et album i et Piwigo-galleri | Image |

**Piwigo Albumbygger** skal ligge på galleriets egen webadresse for at kunne hente fra Piwigo. De andre kører lokalt.

## Værktøjer

Værktøjerne ligger i mappen `tools/`. De følger ikke Builder-standarden, men er hver især én fil, der virker uden internet (undtagen ét, se nedenfor).

| Navn | Fil | Version | Gør |
|---|---|---|---|
| Søgeside · Bygger | `tools/soegeside-bygger.html` | 1.2 | laver en søgeside ud af en mappe med færdige filer |
| Metadata-hjælper | `tools/metadata-hjaelper.html` | 1.2 | laver en AI-prompt til Dublin Core og kontrollerer svaret |
| Feltoptager | `tools/feltoptager.html` | 1 | optager lyd, rydder op og gemmer som mp3 eller som lydfortælling (HTML) |
| Lydlab | `tools/lydlab.html` | 1 | gør en lydoptagelse klar: mono, jævn lyd og fast lydstyrke, ud kommer en mp3 |
| Fotolab | `tools/fotolab.html` | 4 | farve-effekter, billedformat og Polaroid med håndskrevet tekst |
| Musikpakke · Bygger | `tools/musikpakke-bygger.html` | 1 | laver en offline musik-app (PWA) med 3–5 samlinger som zip-fil |
| Skyggeramme | `tools/skyggeramme.html` | – | sætter en blød skygge om et skærmbillede til wikien |
| Kodeords- og hash-generator | `tools/password-hash.html` | – | laver kodeord og bcrypt-hashes til PHP, SQL, `.htpasswd` og `.env` |

`–` betyder, at der ikke står noget versionsnummer i filen.

**Kodeords- og hash-generatoren kræver internet.** Den henter kode og skrifttyper fra `cdn.tailwindcss.com`, `cdn.jsdelivr.net` og Google Fonts. Den virker derfor ikke uden forbindelse, og de tre steder kan se, at den bliver åbnet. De øvrige syv værktøjer og alle byggere henter ikke noget udefra.

## Andet

| Navn | Fil | Hvad det er |
|---|---|---|
| Dashboard | `bygger-dash.html` | forside med links til byggere og værktøjer |
| Søgeindeks · Online | `sbtv/` | den samme søgeside som Søgeside · Bygger, men med login, upload og administration på et webhotel med PHP. Se `sbtv/LAES-MIG.md` |
| Builder-standarden | `builder-standard.md` | fælles regler for byggerne, version 2.1 |
| Husregler | `claude.md` | regler for samarbejdet med Claude |

## Det hænger sammen

- **Feltoptager → Lydfortælling · Bygger.** Feltoptageren gemmer lydfortællinger i Lydfortælling · Byggerens filformat (nu 4). Tælles filformatet op i byggeren, skal Feltoptageren rettes med, ellers kan byggeren ikke indlæse filerne.
- **Byggerne → Søgeside · Bygger og Søgeindeks · Online.** Søgesiderne læser Dublin Core-oplysningerne i de færdige filer.
- **Samling · Bygger** samler færdige filer fra de andre byggere.

## Nævnt i standarden, men ikke i repoet endnu

Præsentation · Bygger (v1.0), Før og Nu · Bygger, Vejledningsbygger og PWA · Bygger. Standarden beskriver dem, men filerne ligger ikke her.

## Filnavne

Byggere hedder `navn-bygger.html`. Værktøjer hedder det enkleste, der passer (`fotolab.html`), også uden versionsnummer i navnet, for adressen skal være den samme fra version til version. Små bogstaver, bindestreg mellem ordene og ingen æ, ø og å.
