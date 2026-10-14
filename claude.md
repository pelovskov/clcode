# CLAUDE.md – Retningslinjer for dette repo

Denne fil læser Claude automatisk hver gang vi starter. Den er repoets "husregler".

## Hvad er dette repo?

Samling af selvstændige builder-værktøjer til lokalhistorie og formidling. Hver builder er en webapp i **én enkelt HTML-fil** (singlefile), som genererer endnu en selvstændig HTML-fil (fx en e-bog, et fotoalbum eller en lydfortælling).

Værktøjerne bruges af frivillige og ældre brugere, så de skal være enkle, robuste og lette at forstå.

## Grundregler

- **Én fil pr. builder.** HTML, CSS og JavaScript ligger i samme fil.
- **Ingen eksterne afhængigheder.** Ingen CDN-links, ingen frameworks, ingen byggeprocesser, ingen internetforbindelse nødvendig. Alt skal virke offline.
- **Langtidsholdbarhed først.** Brug almindelig, standardiseret HTML, CSS og JavaScript. Undgå nye eller eksperimentelle funktioner, som måske forsvinder. Filerne skal kunne åbnes om mange år.
- **Tilgængelighed.** Stor og letlæselig skrift, høj kontrast, tydelige knapper, god tastaturnavigation, meningsfulde alt-tekster og semantisk HTML. Sigt mod WCAG 2.2 AA.
- **Følg `BUILDER-STANDARD.md`.** Den er den fælles specifikation. Hvis en opgave strider mod standarden, så sig det, før du ændrer noget.

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

## Test

Efter en ændring skal du beskrive, hvordan jeg selv kan afprøve den i browseren: hvilken fil jeg åbner, og hvad jeg skal klikke på for at se, at det virker.

## Filnavne og struktur

- Filnavne med små bogstaver og bindestreger, uden æ, ø og å (fx `album-bygger.html`).
- Hver builder får, når det er relevant, sin egen korte beskrivelse i `README.md`.
- Dokumentation skrives i Markdown.

## Ting jeg er særligt optaget af

- At resultatet kan bruges af mennesker, der ikke er vant til it.
- At de genererede filer er selvstændige og kan gemmes, deles og åbnes uden installation.
- At metadata (fx Dublin Core) bevares og håndteres efter standarden.

<!-- Tilføj løbende nye regler her, når du opdager noget, Claude bør huske. -->
