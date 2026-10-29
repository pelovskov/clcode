# Søgeindeks · Online

Onlineudgaven af *Søgeside · Bygger*. Søgesiden ligger på et webhotel med PHP og
opdaterer sig selv, hver gang nogen lægger en fil op. Filerne er de samme som før:
singlefile-HTML fra Byggerne, henvisninger og videokort.

## Filerne

| Fil | Hvad den gør |
|---|---|
| `index.php` | Søgesiden. Den er offentlig. |
| `upload.php` | Læg filer op (kræver login). |
| `admin.php` | Kun for superadmins: slet filer, byg søgesiden forfra, ret overskrift, opret og luk redaktører, se hvem der har gjort hvad. |
| `rtv.php` | Roskilde TV's arbejdsliste med fælles prikker: rød (bør have et mediekort), gul (i gang) og grøn (færdig). Kræver login. Viser `rtv-skabelon.html`. |
| `rtv-skabelon.html` | Selve listen over Roskilde TV's udsendelser. Laves af `tools/lav-rtv-arbejdsliste.py` i repoet. Ret ikke i hånden. |
| `statistik.php` | Besøg, mest åbnede historier og søgeord. Kræver login. |
| `vis.php` | Mellemstation, som tæller et klik og straks åbner filen. Søgesiden og forsiden linker hertil. |
| `kode.php` | Skift din egen adgangskode. Redaktører med en engangskode sendes hertil første gang. |
| `login.php`, `logout.php` | Log ind og ud. |
| `lav-hash.php` | Laver adgangskode-linjer til `config.php`. Gemmer intet. Det samme kan gøres uden server med `tools/password-hash.html`. |
| `config.php` | Brugere og indstillinger. Den eneste fil, du normalt retter i. |
| `faelles.php` | Fælles kode, herunder skabelonen til søgesiden. |
| `filer/` | Alle de filer, der er lagt op, plus en færdig `soegeside.html`. |
| `filer/thumbs/` | Små billeder (240 × 180 px) til søgesiden og forsiden. Laves af sig selv. |
| `grafik/` | Foreningernes logoer, fx `grafik/rmu.png`. Skal kunne ses udefra, så de må **ikke** ligge i `data/`. |
| `data/` | Indeks, log, papirkurv, `foreninger.json`, `brugere.json` med redaktørerne, `rtv-status.json` med prikkerne og `statistik.json` med tællerne. Spærret for adgang udefra. |

## Sådan sætter du det op

1. Læg hele mappen op på webhotellet, fx i `/arkiv/`. Kopiér `config-eksempel.php` til
   `config.php` på webhotellet. Selve `config.php` med adgangskoderne ligger **ikke** i
   repoet, fordi det er offentligt. Det samme gælder `data/` (indeks, log og login-forsøg),
   som serveren selv opretter og fylder.
2. Opret superadmins: Lav en hash med `tools/password-hash.html` på din egen computer
   eller med `https://ditdomæne.dk/arkiv/lav-hash.php`, og kopiér den linje, du får.
3. Sæt linjen ind i `config.php` under `$BRUGERE`, og skriv brugernavnet i `$ADMINS`,
   fx `['poul']`. Dine eksisterende bcrypt-hashes (`$2b$` eller `$2y$`, cost 12) kan bruges direkte.
4. Sæt `$TILLAD_HASH_SIDE = false;`, når superadmins er oprettet.
5. Log ind via `upload.php`, og ret overskriften på admin-siden.
6. Opret redaktører på admin-siden under **Brugere** (se nedenfor).

## Brugere og roller

| | Superadmin | Redaktør |
|---|---|---|
| Lægge filer op | ✓ | ✓ |
| Se og sætte prikker i arbejdslisten (`rtv.php`) | ✓ | ✓ |
| Se statistikken (`statistik.php`) | ✓ | ✓ |
| Skifte sin egen adgangskode | i `config.php` | ✓ på siden *Skift adgangskode* |
| Slette filer, bygge søgesiden forfra, rette overskrift | ✓ | – |
| Oprette, lukke og genåbne redaktører | ✓ | – |

- **Superadmins** står i `config.php` i både `$BRUGERE` og `$ADMINS`. De kan ikke ændres eller fjernes fra hjemmesiden, så man aldrig kan låse sig selv ude.
- ⚠️ **Er `$ADMINS` tom, er alle brugere i `config.php` superadmins.** Skriv derfor altid superadmins i `$ADMINS`.
- **Redaktører** oprettes på admin-siden under **Brugere**: Skriv et brugernavn, og tryk *Opret redaktør*. Der vises en **engangskode** én gang. Giv den videre personligt eller i en sms. Første gang redaktøren logger ind, skal vedkommende vælge sin egen adgangskode på mindst 10 tegn. Du kender aldrig deres rigtige kode.
- **Glemt kode:** tryk *Ny engangskode* ud for brugeren.
- **Luk:** Brugeren bliver logget ud med det samme og kan ikke logge ind, før en superadmin trykker *Genåbn*. Brugeren slettes ikke, så loggen viser stadig, hvem der har gjort hvad.
- Redaktørerne gemmes i `data/brugere.json` med bcrypt-hashes. Filen ligger aldrig i repoet.

## Roskilde TV's arbejdsliste med prikker

`rtv.php` viser alle Roskilde TV's udsendelser for dem, der er logget ind. Hver video har en prik:

- **Rød:** bør have et mediekort.
- **Gul:** mediekortet er ved at blive lavet. Navn og dato står ved prikken.
- **Grøn:** færdig og lagt i søgeindekset. Grøn kommer **af sig selv**, når der ligger et mediekort til videoen i søgeindekset.

Tryk på prikken for at skifte farve, eller hold musen over en video og tryk **R**, **Y**, **G** eller **0**. Prikkerne gemmes i `data/rtv-status.json`, og alle, der er logget ind, ser de samme. Siden henter nye prikker hvert minut.

Knappen *Lav mediekort* åbner Mediekort · Bygger på adressen i `$MEDIEKORT_ADRESSE` i `config.php`, normalt repoets GitHub Pages.

Efter opdateringen: Tryk én gang på **Byg søgesiden forfra** på admin-siden, så de mediekort, der allerede ligger i søgeindekset, også giver grønne prikker.

## Statistik

`statistik.php` viser, hvor meget søgeindekset bliver brugt. Alle, der er logget ind, kan se siden.

- **Besøg:** hvor mange gange forsiden og søgesiden er vist, pr. dag og pr. måned.
- **Mest åbnede historier:** Linkene på forsiden og søgesiden går gennem `vis.php`, som lægger én til tælleren og straks åbner filen. Læseren mærker ingenting. Klikkene tælles pr. **filnavn**. Lægges en rettet fil op med samme navn, fortsætter tællingen, og slettes en fil, bliver dens tal stående.
- **Foreninger og emner:** klikkene lagt sammen pr. udgiver og pr. emneord.
- **Søgeord:** hvad folk søger efter, og hvilke søgninger der ikke gav træf. Et søgeord tælles, når man klikker på et træf, forlader siden eller begynder på en ny søgning, så det er "domkirken" og ikke "d", "do", "dom" … der tælles.

Der gemmes **kun tal** i `data/statistik.json`: ingen IP-adresser, ingen cookies og intet om, hvem der har besøgt siden. Derfor kræver det ikke et cookie-banner. Søgemaskinernes robotter og dem, der er logget ind, tælles ikke. Tallene er en indikation, ikke en præcis optælling. Den offline søgeside i `filer/soegeside.html` tæller ingenting.

Tællingen begynder, den dag `vis.php` og de nye filer bliver lagt op. Den kan ikke tælle bagud.

## Tjek at data-mappen er spærret

Åbn `https://ditdomæne.dk/arkiv/data/indeks.json` i browseren. Du skal få
**Forbidden** (403). Får du filens indhold at se, læser serveren ikke `.htaccess`
(det sker på nginx-servere). Så skal webhotellets support spærre mappen `data/`.

Får du **500 Internal Server Error** på filerne i `filer/`, tillader webhotellet ikke
`.htaccess` der. Slet så `filer/.htaccess`. Systemet tager kun imod `.html`-filer
alligevel, så det er en ekstra sikring, ikke den eneste.

## Hvordan det virker

- Kun `.html`/`.htm`-filer med metadata fra Byggerne tages imod.
  Filer uden metadata afvises med en forklaring.
- En fil med samme navn som en eksisterende erstatter den gamle, også hvis store og
  små bogstaver er forskellige.
- Filerne sendes op én ad gangen med en statuslinje, så mange store filer ikke
  tilsammen sprænger serverens grænse. Grænsen pr. fil vises på upload-siden.
- Slettede filer lægges i `data/papirkurv/` og kan hentes tilbage med FTP,
  indtil papirkurven tømmes på admin-siden.
- Lægger du filer op eller sletter du dem med FTP, så tryk
  **Byg søgesiden forfra** på admin-siden.
- Henvisninger med andet end en `http(s)`-adresse bliver ikke til links.
- **Små billeder:** Når en fil lægges op, gemmes det første billede i den som en lille
  JPEG i `filer/thumbs/`. Det kræver GD på webhotellet. Filer uden billede får et ikon.
  Filer, der lå der før denne funktion kom til, får billeder, når du trykker
  **Byg søgesiden forfra** på admin-siden. Billederne slettes sammen med filen.
- Søgesiden og forsiden åbner filerne i samme faneblad med `?hjem=` i adressen, så
  Hjem-knappen i filerne fører tilbage til søgningen – med de samme filtre.
- Fem forkerte adgangskoder fra samme sted giver 15 minutters pause.

## Offline-kopi

Mappen `filer/` indeholder altid en opdateret `soegeside.html`. Hent hele mappen
med FTP – også undermappen `thumbs/` – så har du et færdigt arkiv til USB-stik eller
tidskapsel, der virker uden internet, præcis som med den lokale Søgeside · Bygger.

## Flere foreninger

- **Ét fælles arkiv:** alle får en bruger i samme installation. Filteret *Område*
  på søgesiden sorterer efter `publisher`, så hver forening kan findes for sig.
- **Hver sit arkiv:** kopiér hele mappen til en ny undermappe med sin egen
  `config.php`. Installationerne deler hverken filer eller login.

## Én ting at vide om sikkerhed

De filer, der lægges op, er HTML med JavaScript og kører på samme domæne som
upload- og admin-siden. Giv derfor kun login til folk, du stoler på.
