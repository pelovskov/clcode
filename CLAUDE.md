# CLAUDE.md – Retningslinjer for dette repo

Denne fil læser Claude automatisk hver gang vi starter. Den er repoets "husregler".

## Hvad er dette repo?

Samling af selvstændige builder-værktøjer til lokalhistorie og formidling. Hver builder er en webapp i **én enkelt HTML-fil** (singlefile), som genererer endnu en selvstændig HTML-fil (fx en e-bog, et fotoalbum eller en lydfortælling).

Værktøjerne bruges af frivillige og ældre brugere, så de skal være enkle, robuste og lette at forstå.

## Grundregler

- **Én fil pr. builder.** HTML, CSS og JavaScript ligger i samme fil.
- **Ingen eksterne afhængigheder.** Ingen CDN-links, ingen frameworks, ingen byggeprocesser, ingen internetforbindelse nødvendig. Alt skal virke offline. Undtagelserne er det, et værktøj selv skal hente eller vise: Piwigo Albumbygger henter fra galleriet og skal ligge på dets adresse, Mediekort viser YouTube-videoer, og Søgeindeks · Online (`sbtv/`) kører med PHP på webhotellet.
- **Langtidsholdbarhed først.** Brug almindelig, standardiseret HTML, CSS og JavaScript. Undgå nye eller eksperimentelle funktioner, som måske forsvinder. Filerne skal kunne åbnes om mange år.
- **Tilgængelighed.** Stor og letlæselig skrift, høj kontrast, tydelige knapper, god tastaturnavigation, meningsfulde alt-tekster og semantisk HTML. Sigt mod WCAG 2.2 AA.
- **Følg `builder-standard.md`.** Den er den fælles specifikation. Hvis en opgave strider mod standarden, så sig det, før du ændrer noget.

## Sprog og tone

- Al tekst i brugerfladen, kommentarer, README-filer og commit-beskeder skrives på **dansk**.
- Brug et enkelt, venligt og letforståeligt sprog. Undgå fagjargon, eller forklar den kort.
- Når du forklarer noget for mig, så skriv, som om jeg er en nysgerrig ikke-programmør: konkret og uden for mange forkortelser.

## Sådan arbejder vi

- **Spørg før du antager.** Er en opgave uklar, så stil et kort spørgsmål først.
- **Én ting ad gangen.** Hold ændringer små og afgrænsede, så jeg kan gennemse dem i GitHub Desktop.
- **Gennemgå før du retter.** Når jeg beder om en gennemgang, så giv en liste over afvigelser og ret ikke noget, før jeg har sagt til.
- **Bevar det eksisterende.** Omskriv ikke store dele af en fil uden at spørge. Ændr kun det, opgaven kræver.
- **Fortæl hvad du har ændret.** Slut hver opgave med en kort oversigt: hvilke filer, hvad der er ændret, og hvad jeg bør teste.
- **Ingen sletning uden at spørge.** Slet eller omdøb aldrig filer uden min godkendelse.
- **Commits** skrives på dansk med en kort, beskrivende besked (fx "Ret kontrast på knapper i Album-bygger").

## Fast arbejdsgang

1. Arbejdet laves på arbejdsgrenen og afprøves i en browser.
2. Ændringen gemmes (commit) og skubbes op (push).
3. Spørg: "Skal jeg lave pull request og flette?" Først når jeg siger ja, laves pull request til `main`, som flettes. Tjek bagefter, at ændringen er kommet med på `main`.
4. Mind mig om at hente med Fetch og Pull i GitHub Desktop og **aldrig bruge Force push**. Force push har én gang overskrevet arbejde på `main`.

## Det skal følge med, når noget ændres

- **En bygger eller et værktøj ændres:** tæl versionsnummeret op, og ret `oversigt.md` og `bygger-dash.html`.
- **Filformatet ændres:** tæl `builderVersion` op (byggeren afviser filer med et lavere tal), og tjek "Ting der hænger sammen" nedenfor.
- **Standarden ændres:** tæl versionen i `builder-standard.md` op, skriv i ændringsloggen, og ret versionsnummeret i `oversigt.md`.
- **Noget større kommer til eller bliver afsluttet** (en ny bygger, en ny forening, et åbent spørgsmål der bliver afklaret): ret `projekt-resume.md`.
- **Nyt tema eller ny udgiver:** skrives først i standarden og kopieres derefter uændret til alle byggere. En ny udgiver skal også i Metadata-hjælper og i `sbtv/data/foreninger.json`.

## Test

Efter en ændring skal du beskrive, hvordan jeg selv kan afprøve den i browseren: hvilken fil jeg åbner, og hvad jeg skal klikke på for at se, at det virker.

## Filnavne og struktur

- Filnavne med små bogstaver og bindestreger, uden æ, ø og å (fx `album-bygger.html`).
- Byggere hedder `navn-bygger.html` og ligger øverst i repoet. Værktøjer ligger i `tools/`. Der står ikke versionsnummer i filnavnene, så adressen er den samme fra version til version.
- `oversigt.md` er overblikket over alle byggere og værktøjer med versioner og filformater.
- Dokumentation skrives i Markdown.
- Min Mac skelner ikke mellem store og små bogstaver i filnavne. En omdøbning, der kun ændrer store og små bogstaver (fx `claude.md` til `CLAUDE.md`), kan derfor ikke ses i GitHub Desktop og skal laves af Claude.

## Ting der hænger sammen

- **Den frie udgave** (`fri/`) laves af opskriften `fri/lav-fri-udgave.py` ud fra de almindelige byggere. Når en bygger eller Søgeside · Bygger ændres, køres opskriften igen (`python3 fri/lav-fri-udgave.py`), så den frie udgave følger med. Ret aldrig i de lavede filer i `fri/` i hånden. Kun `fri/index.html`, `fri/LAES-MIG.md` og huskesedlerne `fri/vejledning-*.html` er skrevet i hånden. Manuskripterne til screencasts ligger i `vejledninger/`. Ændres en bygger, så trinene ikke passer længere, skal huskesedlen og manuskriptet rettes med. Opskriften laver også `fri/lokalhistorier-fri-udgave.zip`, som kan hentes fra forsiden, så kør den igen, også når forsiden eller vejledningen er rettet.

- `tools/rtv-arbejdsliste.html` og `sbtv/rtv-skabelon.html` laves af `tools/lav-rtv-arbejdsliste.py` ud fra det offentlige `roskilde-tv-videoarkiv.html`. Når arkivet opdateres, køres opskriften igen. `sbtv/rtv.php` viser skabelonen med fælles prikker for dem, der er logget ind, og grøn kommer af sig selv fra `video`-feltet i søgeindekset (Mediekorts `videoId`). Arbejdslisten (med knappen «Lav mediekort») linkes kun fra dashboardet, aldrig fra portalen. Knappen bruger `mediekort-bygger.html?yt=…&titel=…`, så den adresse skal Mediekort blive ved med at forstå.

- `tools/feltoptager.html` laver lydfortællinger i **Lydfortælling · Byggerens filformat** og skriver selv `builderVersion`. Tælles `BUILDER_VERSION` op i `lydfortaelling-bygger.html`, skal `builderVersion` i Feltoptageren rettes med, ellers afviser byggeren filerne.

- `billedarkiv/billedarkiv.html` eksporterer albums med en `.json`-fil ved hvert billede, som **Album · Bygger** læser (kendes på `exportedFilename`). Ændres feltnavnene i eksporten, skal `album-bygger.html` og standardens afsnit 8.1 rettes med.

- `emneord.md` er foreningernes fælles emneord. Udgiveren siger, hvem der har lavet en historie, og de fælles emneord siger, hvad den handler om. Nye emneord skrives på listen, før de tages i brug. Listen står også i `tools/emneord.html`, som skal rettes med.

- I søgeindekset (`sbtv/`) hører nøglen i `data/foreninger.json` (fx `rmu`) sammen med udgiveren i `PUBLISHERS`. Logoerne skal ligge i `sbtv/grafik/`, ikke i `data/`, som er spærret for besøgende.

## Sikkerhed

- Repoet er **offentligt**. Læg aldrig adgangskoder, hashes, nøgler, logfiler eller driftsdata fra serveren i det (fx `sbtv/config.php` og `sbtv/data/`). Brug eksempelfiler uden rigtige værdier.
- Tag imod adresser fra brugere (fx `?hjem=`) med forsigtighed: kun relative adresser og `http(s)` er tilladt, aldrig `javascript:` eller `data:`.
- Adgangskoder til `sbtv/config.php` gemmes kun som bcrypt-hash. Hashen laves med `tools/password-hash.html` på min egen computer eller med `sbtv/lav-hash.php` på serveren.

## Ting jeg er særligt optaget af

- At resultatet kan bruges af mennesker, der ikke er vant til it.
- At de genererede filer er selvstændige og kan gemmes, deles og åbnes uden installation.
- At metadata (fx Dublin Core) bevares og håndteres efter standarden.

<!-- Tilføj løbende nye regler her, når du opdager noget, Claude bør huske. -->

