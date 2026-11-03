# SFB-afspiller

En afspiller, der viser historier **direkte fra zip-filer**, uden at noget skal pakkes ud. Den er tænkt til film, podcastserier og senere store gallerier, som er for store til at ligge inde i én HTML-fil, og til de mange, der har svært ved zip-filer, især på telefoner og tablets.

Status: **version 0.12, prøveudgave.** Den virker på computeren og som app på telefoner og tablets, og den har en **historiehylde**, hvor historier hentes ind i appen med ét tryk.

## Sådan bruges den

1. Åbn `index.html` i browseren.
2. Vælg en eller flere zip-filer, eller træk dem ind i feltet.
3. Afspilleren viser et lille **bibliotek** med titel, billede, type, varighed og beskrivelse.
4. Tryk **Afspil**. Historien vises med sin egen side, og lyd og video hentes direkte fra zip-filen. Tryk **← Biblioteket** for at komme tilbage.
5. **Tjek filerne** sammenligner lydfilernes og videoernes kontroltal (SHA-256) med dem, der står i historiens seddel, og siger, om alt er helt og uændret.

Af sikkerhedsgrunde skal zip-filerne vælges igen, hver gang afspilleren åbnes.

## Historiehylden

Når afspilleren ligger på webhotellet, viser den øverst **Historiehylden**: de historier, der ligger klar på webhotellet, med titel, billede, varighed og størrelse.

- Tryk **Hent til appen**. En bjælke viser, hvor langt den er. Historien hentes i stykker på 4 MB og kontrolleres, før den gemmes, så en halv eller forkert fil aldrig havner i appen. Er et stykke forkert, hentes det igen i små bidder. Går det alligevel galt, siger beskeden hvorfor, og bagefter står en «Teknisk besked», som kan sendes videre til den, der passer afspilleren.
- Historien gemmes i appens eget lager og ligger derefter i **Dit bibliotek**, også næste gang appen åbnes og uden internet. Ingen skal lede efter en zip-fil.
- **Fjern fra appen** sletter den igen. Den kan altid hentes igen fra hylden.
- **Ikke længere på hylden:** Bliver en historie fjernet fra hylden, fx med FTP, ligger den stadig i appen hos dem, der har hentet den. Kortet i biblioteket siger «Ikke længere på hylden», og brugeren bestemmer selv, om den skal blive eller fjernes. Beskeden vises kun, når appen faktisk har kunnet se hylden, så intet ser forsvundet ud, når der ikke er net.
- **Nyt på hylden:** Hylden hentes, når appen åbnes. Ligger appen i baggrunden på telefonen og kommer frem igen efter mere end 10 minutter, kigger den selv efter nyt. Knappen **Opdatér hylden** gør det med det samme. Ved knappen står, hvornår hylden sidst blev opdateret, og om der er kommet nye historier.

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

### Lyd med slukket skærm, fx i bilen

- Lyden spiller videre, når skærmen slukker. Afspilleren fortæller telefonen, hvad der spiller, så titel og billede vises på den låste skærm med knapperne Afspil/Pause, Forrige og Næste. I mange biler med Bluetooth kan man også styre med bilens egne knapper.
- Knappen **Hold skærmen tændt** øverst i en åben historie sørger for, at skærmen ikke slukker, fx når telefonen sidder i en holder. Den bruger mere strøm og slås fra, når historien lukkes. Knappen vises kun, hvor browseren kan det.
- iPhone og iPad er mere strikse end Android. Stopper lyden dér, når skærmen slukker, så brug **Hold skærmen tændt**.

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

Desuden kan den åbne:

- **Album og de andre byggeres HTML-filer**, når de er pakket i en zip-fil. Billederne ligger inde i HTML-filen.
- **Et galleriarkiv** fra værktøjet Galleriarkiv (standardens afsnit 5.8): et helt album fra Piwigo med underalbum. Zip-filen kan komme fra Galleriarkiv selv, eller man kan selv pakke mappen, fx med Finders «Komprimer». Hovedalbummet bliver ét kort i biblioteket med den første miniature og antal billeder. Når det åbnes, kan man trykke sig ind i underalbummene og tilbage med brødkrummerne øverst, og billederne vises i stort format. Links til nettet åbner i en ny fane.

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
3. **Historiehylden** – lavet (version 0.3, gjort mere robust i 0.4 og 0.5, og i 0.6 rettet, så den virker i nyere browsere). Ligger på foreningens webhotel, fordi GitHub Pages har en grænse på 100 MB pr. fil.
4. **Medlemsmødet:** et program for aftenen, der kører én ting ad gangen i fuld skærm, mens den, der viser, trykker Næste.
5. **Gallerier** – lavet (version 0.9) med galleriarkivet fra værktøjet Galleriarkiv, som gemmer et helt album fra Piwigo med underalbum. Prøvet i november 2026 med Roskilde Domkirke (572 billeder i 9 album): Mappen blev pakket med Finders «Komprimer», lagt på hylden og hentet til en mobiltelefon. Zip-filen på 310 MB kom hurtigt igennem, og albummet virker som det skal, også uden internet.
6. **Lyd med slukket skærm** – lavet (version 0.7). Prøvet på Android og iOS. Mangler at blive prøvet i bilen med Bluetooth.

### Ønsker til hylden og biblioteket

Hylden kan bruges som foreningens egen kanal, fx til «Ugens lydhistorie» eller «Nye billeder», uden abonnement, platform eller reklamer. Album og de andre byggeres HTML-filer virker allerede, når de pakkes i en zip-fil. Disse ønsker er noteret, og de laves én ad gangen:

1. **Fold hylden sammen** – lavet (version 0.8). Tryk på overskriften «Historiehylden». Antallet af historier står ved overskriften, og appen husker, om hylden var foldet sammen.
2. **Albumkort** – lavet (version 0.8). Et album får sit første billede på kortet, typen «Billedalbum», antal billeder og knappen «Åbn» i stedet for «Afspil». Senere måske: et forsidebillede, man selv vælger i Album · Bygger.
3. **Mærket «Nyt»** – lavet (version 0.12). Historier, man ikke har haft på hylden før, får mærket «Nyt», og antallet står ved overskriften, fx «7 historier · 1 ny». Appen husker selv, hvad der har været vist, uden beskeder på telefonen. Mærket bliver stående, mens appen er åben, og er væk næste gang. Første gang appen bruges, er intet nyt.
4. **Adminside** på webhotellet, fx i `afspiller/admin/`. Den er en selvstændig ting med sit eget login og er ikke lagt sammen med Foreningers søgeindeks. Se beskrivelsen under «Adminsiden» nedenfor. Ønsker fra november 2026:
   - Login for 2 brugere. Adgangskoderne gemmes kun som bcrypt-hash i en `config.php` på webhotellet, aldrig i repoet.
   - Upload af zip-filer fra browseren. Store filer sendes i stykker, siden tjekker, at zip-filen er lavet af byggerne, og filnavnet rettes.
   - En venteposition, fx mappen `venter/`, som besøgende ikke kan se. Her kan zip-filer ligge og vente på at blive brugt igen, fx julehistorier, og de kan flyttes frem og tilbage med én knap.
   - Sletning med «Er du sikker?», helst kun fra ventepositionen.
   - Datoer for «vises fra» og «flyttes til venteposition». Hylden tjekker datoerne, hver gang den åbnes, så der skal ikke køre noget på serveren.
   - Afgjort i november 2026 og lavet i version 0.11: Når en historie ikke længere er på hylden, fjerner appen den ikke af sig selv. Kortet i biblioteket får en linje, fx «Ikke længere på hylden – fjern fra appen?», og brugeren bestemmer selv. Webhotellet kan ikke slette kopien på telefonen, og appen tager aldrig noget fra folk uden at spørge.
   - Senere måske: skift en historie ud med en rettet udgave, og se, hvor tit hver historie hentes.
5. **Tydeligere pil** – lavet (version 0.10). En rund knap med en vinkel og teksten «Skjul» eller «Vis» til højre for overskriften.
6. **«Dine egne zip-filer»** – foldes sammen fra start, når der er en hylde (version 0.10), og appen husker, hvis man folder det ud. Afsnittet bruges mest til at prøve ting af. Det er foldet sammen, så det ikke fylder for dem, der kun bruger hylden. Det skal blive, fordi det er nyttigt til medlemsmøder med zip-filer på et USB-stik, til zip-filer sendt med mail eller lagt i Google Drev og til historier, der ikke skal på den offentlige hylde. Kortene i biblioteket viser, hvor historien kommer fra: «✓ Gemt i appen» eller «Din egen zip-fil – vælges igen næste gang» (version 0.12).
7. **Opfriskning af hylden** – lavet (version 0.10): af sig selv efter mere end 10 minutter i baggrunden og med knappen «Opdatér hylden».

## Adminsiden (beskrivelse til godkendelse)

**Status: udkast, november 2026.** Intet er lavet endnu. Beskrivelsen læses og godkendes, før der skrives kode.

### Hvad den skal kunne

En side på webhotellet, hvor to personer kan logge ind og passe hylden uden FTP:

- **Se hylden og ventepositionen** i to lister med titel (læst inde i zip-filen), filnavn, størrelse, dato og eventuelle datoer for hvornår den vises og flyttes.
- **Lægge en zip-fil op** fra computeren. Filen sendes i stykker på 1 MB, så webhotellets grænse for upload ikke gør noget, og der er en bjælke, der viser, hvor langt den er. Bagefter tjekker siden, at det er en zip-fil med en historie fra byggerne (en HTML-fil med sedlen), og retter filnavnet efter reglerne: små bogstaver, tal og bindestreger. Findes navnet allerede, spørger siden, om den gamle skal erstattes.
- **Vælge, hvor en ny fil skal hen:** direkte på hylden eller i ventepositionen.
- **Flytte** en zip-fil fra hylden til ventepositionen og tilbage med én knap.
- **Slette** en zip-fil, men kun fra ventepositionen og kun efter «Er du sikker?». Det, der ligger på hylden, skal altså først flyttes. Det giver en ekstra sikkerhed mod at slette ved et uheld.
- **Datoer:** For hver zip-fil kan der stå «vises fra» og «flyttes til venteposition». Så kan julehistorierne lægges klar i oktober og forsvinde af sig selv i januar.

### Sådan virker datoerne uden noget, der kører på serveren

Datoerne gemmes i en lille fil på webhotellet. Hver gang nogen åbner hylden, kigger `hylde.php` på datoerne: Er «vises fra» nået, flyttes filen fra ventepositionen til hylden, og er «flyttes til venteposition» nået, flyttes den den anden vej. Det sker altså, første gang nogen kigger efter datoen. Afspilleren mærker ikke andet, end at hylden ændrer sig. Er en historie flyttet væk, siger kortet i appen «Ikke længere på hylden» (version 0.11).

### Filerne på webhotellet

```
afspiller/
  hylde.php              ← som i dag, men flytter også efter datoerne
  hylde/                 ← det, der vises på hylden (som i dag)
  venter/                ← ventepositionen; kan ikke ses af besøgende
  admin/
    index.php            ← login og selve siden
    config.php           ← de to brugere med bcrypt-hash; ALDRIG i repoet
    config-eksempel.php  ← skabelon uden rigtige værdier, i repoet
    data/                ← datoer, login-forsøg og halve uploads; kan ikke ses af besøgende
```

Adgangen til `venter/` og `admin/data/` spærres med en lille `.htaccess`-fil, som virker på jeres webhotel (LiteSpeed).

### Sikkerhed

Samme opskrift som login i Søgeindeks · Online, men med sine egne filer, så adminsiden er selvstændig:

- Adgangskoder gemmes kun som bcrypt-hash. Hashen laves med `tools/password-hash.html` på din egen computer.
- Efter 5 forkerte forsøg fra samme sted skal man vente 15 minutter.
- Login-cookien kan kun bruges over https og kun af denne side. Man logges ud efter 2 timer uden brug.
- Alle knapper, der ændrer noget, er beskyttet mod at blive sendt fra en fremmed side (CSRF).
- Filnavne kontrolleres strengt, så ingen kan nå filer uden for `hylde/` og `venter/`.
- `config.php` og `admin/data/` kommer aldrig i repoet (`.gitignore`).

### Rækkefølge

1. **Login, de to lister, flyt og slet.** Det kan prøves med de zip-filer, der allerede ligger på hylden.
2. **Upload i stykker** med tjek af zip-filen og navnet.
3. **Datoer**, og `hylde.php` flytter efter dem.

Hvert trin prøves på webhotellet, før det næste laves.

### Spørgsmål, før vi går i gang

- Er adressen `…/over/afspiller/admin/` god?
- Skal en sæsonhistorie, der kommer tilbage fra ventepositionen, have mærket «Nyt» igen? Forslaget er ja, fordi den så bliver bemærket, når den dukker op hvert år.

Se også visionen i `projekt-resume.md` under «Åbne spørgsmål og idéer».
