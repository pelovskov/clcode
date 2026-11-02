# SFB-afspiller

En afspiller, der viser historier **direkte fra zip-filer**, uden at noget skal pakkes ud. Den er tænkt til film, podcastserier og senere store gallerier, som er for store til at ligge inde i én HTML-fil, og til de mange, der har svært ved zip-filer, især på telefoner og tablets.

Status: **version 0.4, prøveudgave.** Den virker på computeren og som app på telefoner og tablets, og den har en **historiehylde**, hvor historier hentes ind i appen med ét tryk.

## Sådan bruges den

1. Åbn `index.html` i browseren.
2. Vælg en eller flere zip-filer, eller træk dem ind i feltet.
3. Afspilleren viser et lille **bibliotek** med titel, billede, type, varighed og beskrivelse.
4. Tryk **Afspil**. Historien vises med sin egen side, og lyd og video hentes direkte fra zip-filen. Tryk **← Biblioteket** for at komme tilbage.
5. **Tjek filerne** sammenligner lydfilernes og videoernes kontroltal (SHA-256) med dem, der står i historiens seddel, og siger, om alt er helt og uændret.

Af sikkerhedsgrunde skal zip-filerne vælges igen, hver gang afspilleren åbnes.

## Historiehylden

Når afspilleren ligger på webhotellet, viser den øverst **Historiehylden**: de historier, der ligger klar på webhotellet, med titel, billede, varighed og størrelse.

- Tryk **Hent til appen**. En bjælke viser, hvor langt den er. Historien hentes i stykker på 4 MB og kontrolleres, før den gemmes, så en halv eller forkert fil aldrig havner i appen. Går det galt, siger beskeden hvorfor, fx «Filen kom ikke helt igennem».
- Historien gemmes i appens eget lager og ligger derefter i **Dit bibliotek**, også næste gang appen åbnes og uden internet. Ingen skal lede efter en zip-fil.
- **Fjern fra appen** sletter den igen. Den kan altid hentes igen fra hylden.

Afspilleren læser titel og billede direkte inde i zip-filerne over nettet, uden at hente hele filen. Det kræver, at webhotellet kan sende dele af en fil (HTTP Range), og det kan almindelige webhoteller.

Appens lager er begrænset, og især iPhone og iPad kan rydde det, hvis appen ikke bruges i længere tid. Zip-filerne på hylden er de rigtige kopier.

### Sådan lægges afspilleren og hylden på webhotellet

1. Læg hele mappen `afspiller/` på webhotellet, fx `https://sfbbilleder.eu/afspiller/`: `index.html`, `manifest.webmanifest`, `sw.js`, ikonerne, `hylde.php` og mappen `hylde/`.
2. Læg zip-filerne i `afspiller/hylde/` med FTP. Filnavne med små bogstaver, tal og bindestreger, fx `syd-for-banen-fortaeller.zip`.
3. `hylde.php` laver selv listen over zip-filerne, nyeste først. Der skal ikke rettes noget, når der kommer en ny historie.

Kan webhotellet ikke køre PHP, kan listen i stedet skrives i hånden i en fil `hylde.json` ved siden af `index.html`:

```json
{ "hylde": [ { "fil": "hylde/syd-for-banen-fortaeller.zip", "bytes": 196750 } ] }
```

Zip-filerne lægges ikke i repoet på GitHub, kun på webhotellet (`.gitignore` sørger for det).

## Som app på telefon og tablet

Afspilleren skal hentes fra en hjemmeside (https) for at kunne blive en app, fx https://sfbbilleder.eu/afspiller/ (med hylden) eller https://pelovskov.github.io/clcode/afspiller/ (uden hylde).

- **Android:** Åbn adressen i Chrome. Tryk **Installér som app** på siden, eller vælg **Installér app** / **Føj til startskærm** i menuen (de tre prikker).
- **iPhone og iPad:** Åbn adressen i Safari. Tryk på **Del** (firkanten med pilen), og vælg **Føj til hjemmeskærm**.

Derefter ligger SFB-afspilleren som et ikon og åbner i fuld skærm. Den virker også uden internet, fordi hjælpefilen `sw.js` har gemt den på enheden. Zip-filerne vælges som før, fx fra *Overførsler*, *Filer* eller Google Drev.

Åbnes `index.html` direkte fra computeren, virker afspilleren som før, bare ikke som app.

### Filerne i mappen

| Fil | Hvad den gør |
|---|---|
| `index.html` | selve afspilleren |
| `manifest.webmanifest` | fortæller telefonen appens navn, ikon og farver |
| `sw.js` | hjælpefil, der gemmer afspilleren, så den virker uden internet. Tæl `VERSION` op ved en ny version |
| `ikon-192.png`, `ikon-512.png`, `ikon-180.png` | appens ikon (180 er til iPhone og iPad) |
| `hylde.php` | laver listen over zip-filerne i `hylde/` til historiehylden |
| `hylde/` | zip-filerne til hylden (kun på webhotellet) |

## Hvilke zip-filer kan den åbne?

Zip-filer lavet med knappen **Gem hele mappen som zip-fil** i Mediekort · Bygger og Podcastserie · Bygger. De følger mappeformatet i Builder-standardens afsnit 5.7:

```
historien/
  historien.html     ← siden med sedlen (builder-source-data og Dublin Core)
  media/
    historien.mp4    ← eller historien-01.mp3, historien-02.mp3 …
```

Vores zip-filer er lavet **uden komprimering**. Derfor kan afspilleren hente en video som et udsnit af zip-filen uden at læse resten, også når filen er stor. Zip-filer, som man selv har lavet med fx Finders «Komprimer», kan også åbnes, men så skal browseren pakke filerne ud i hukommelsen, og det tager længere tid. Zip-filer over 4 GB kan ikke åbnes endnu.

## Sådan virker den

- Afspilleren læser zip-filens indholdsfortegnelse, finder HTML-siden og læser dens seddel. Afspilleren har altså **intet eget format**. Alt, hvad byggerne laver i mappeformatet, kan vises.
- Når en historie åbnes, vises dens egen side i en ramme. De steder, hvor siden peger på en fil i mappen (fx `media/serie-01.mp3`), peger nu direkte på filen i zip-filen. Knapperne «Gem filen» og «Hjem» skjules, fordi de ikke giver mening inde i afspilleren.
- Alt sker på brugerens egen computer eller telefon. Intet sendes nogen steder hen.

## Mappen er selvstændig

Mappen `afspiller/` bruger intet fra resten af repoet, og intet i repoet afhænger af den. Den kan derfor senere flyttes til sit eget projekt med hele sin historie. Koden til kontroltal er kopieret fra byggerne, så de regner ens.

## Planen

1. **Prototype på computeren** – lavet (version 0.1).
2. **App på telefon og tablet (PWA)** – lavet (version 0.2). Skal prøves på Android-telefoner, en Android-tablet og iPads.
3. **Historiehylden** – lavet (version 0.3, gjort mere robust i 0.4). Ligger på foreningens webhotel, fordi GitHub Pages har en grænse på 100 MB pr. fil.
4. **Medlemsmødet:** et program for aftenen, der kører én ting ad gangen i fuld skærm, mens den, der viser, trykker Næste.
5. **Gallerier:** Album · Bygger kan lægge billederne i mappeformatet, så store gallerier kan vises fra en zip-fil.

Se også visionen i `projekt-resume.md` under «Åbne spørgsmål og idéer».
