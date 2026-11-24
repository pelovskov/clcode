# Det vendbare billede – specifikation

**Status:** Idé og prototype (november 2026)
**Arbejdstitel på byggeren:** Vendebillede · Bygger (navnet er ikke besluttet)
**Outputtype:** Én selvstændig HTML-fil med ét billede, der har en forside og en bagside
**Dublin Core-type:** `Image`
**Referenceprototype:** `vendbart-billede-proeve.html` (kun interaktion og udseende; ikke en bygger)

Byggeren skal følge **Builder-standarden** (nyeste version) på alle punkter, som denne specifikation ikke selv nævner: layout, design-tokens, temaer, de 15 Dublin Core-felter, `builder-source-data`-kontrakten, tilgængelighed og tjeklisten. Hvis denne specifikation og standarden er uenige, gælder standarden, og uenigheden noteres som et åbent spørgsmål.

---

## 1. Baggrund og formål

Før i tiden skrev man bag på fotografierne: hvem, hvor, hvornår og en sætning om, hvad der skete. Bagsiden fulgte med billedet, uanset hvor det endte. Digitale billeder har mistet den bagside.

Det vendbare billede giver et digitalt foto en synlig bagside. Man klikker eller trykker på billedet, og det vender sig som et fysisk fotografi. På bagsiden står teksten med håndskrift på fotopapir.

Arkiverne kalder de to sider **recto** (forside) og **verso** (bagside).

**Målestok:** Formatet skal kunne bruges af almindelige mennesker uden forklaring. Det gælder både for dem, der laver filen, og for dem, der ser den.

---

## 2. Afgrænsning

- Album-bygger og dens output ændres **ikke** af dette arbejde.
- Fotolab ændres **ikke** i fase 1.
- Bagsiden er **kort**, som på et rigtigt foto. Længere tekster hører hjemme i Sidebyggeren.
- Ingen server, intet internet og ingen eksterne biblioteker. Alt skal ligge i én fil.

---

## 3. Outputfilen (det vendbare billede)

### 3.1 Opbygning
- Én HTML-fil. Billedet ligger i den som base64-JPG.
- Håndskriften (Caveat, SIL Open Font License 1.1) ligger i filen som base64-woff2, ligesom i Fotolab. Licensen nævnes i sidefoden.
- `builder-source-data` efter standarden, så byggeren kan åbne filen og redigere den igen.
- Dublin Core-metadata efter standarden, i head og i den sammenfoldelige oversigt nederst.

### 3.2 Forsiden
- Billedet med en smal lys kant, som et papirfoto.
- Kortet har billedets egne proportioner (`aspect-ratio` sættes ud fra billedets bredde og højde).
- **Tegnet på, at der er en bagside:** et hjørne nederst til højre, der er bøjet en anelse op, plus teksten "Vend billedet" under kortet. Det her er det vigtigste designkrav. Hvis brugerne ikke opdager bagsiden, virker formatet ikke.

### 3.3 Bagsiden
- Cremefarvet fotopapir med en svag, ujævn tone.
- Bagsidens tekst skrevet med håndskrift, let skrå (ca. −1,5°).
- Valgfrit arkivnummer i øverste hjørne, med "blyant" (grå).
- Valgfri stempellinje nederst, fx "Fremkaldt aug. 1956".
- Samme størrelse som forsiden.

### 3.4 Vendingen
- Klik, tryk, Enter eller mellemrum vender kortet.
- CSS 3D: `perspective` på beholderen, `transform-style: preserve-3d`, `rotateY(180deg)` og `backface-visibility: hidden` på begge sider, ca. 0,8 sekunder.
- Ved `prefers-reduced-motion: reduce` skifter siderne uden animation.
- Teksten under kortet skifter mellem "Vend billedet" og "Vend tilbage til billedet".

### 3.5 Tilgængelighed
- Kortet er `role="button"` med `tabindex="0"` og `aria-pressed`. Det må **ikke** være et `<button>`-element, for knapper kan ikke indeholde redigerbar tekst i alle browsere.
- Siden, der ikke vises, har `aria-hidden="true"`.
- `aria-label` beskriver, hvad der sker ved tryk. På bagsiden indeholder den selve bagsidens tekst.
- Billedet har alt-tekst. Den hentes fra `dc.description` eller fra et eget felt.
- Synligt fokus. Fuld betjening med tastatur.

### 3.6 Telefoner
- Skal virke ved tryk på både iPhone og Android.
- Kortet fylder skærmens bredde på små skærme, og skriften skalerer med (`clamp`).
- Kendt begrænsning: En HTML-vedhæftning åbner ikke altid pænt på Android. Se 5.2 og åbent spørgsmål 3.

---

## 4. Byggeren

### 4.1 Arbejdsgangen
Det skal være lige så let som at skrive bag på et foto:

1. **Vælg billede.** Byggeren nedskalerer og komprimerer med faste indstillinger, ligesom Album-bygger (fx længste side 2000 px, JPG-kvalitet 0,85; den endelige værdi fastsættes).
2. **Skriv bagsiden.** Et tekstfelt med plads til 4–6 linjer.
3. **Gem.**

Dublin Core-felterne ligger nederst efter standarden og kan udfyldes, men skal ikke udfyldes for at gemme.

### 4.2 Felter til bagsiden
| Felt | Påkrævet | Bemærkning |
|---|---|---|
| Bagsidens tekst | ja | Fri tekst, linjeskift bevares |
| Arkivnummer | nej | Vises med "blyant" i hjørnet |
| Stempellinje | nej | Fx "Fremkaldt aug. 1956" |
| Blæk | nej | Blå (standard), sort, blyant. Samme farver som Fotolab |

Overvejes: forslag til bagsidens tekst ud fra `dc.title`, `dc.date`, `dc.coverage` og `dc.creator`, så man ikke skriver det samme to gange.

### 4.3 Forhåndsvisning
Den levende forhåndsvisning i højre rude skal kunne vendes på samme måde som den færdige fil. Den skal også vise bagsiden med det samme, når man skriver i bagsidefeltet.

### 4.4 Længdegrænse
Bagsiden skal kunne være der uden at rulle. Hvis teksten bliver for lang, gøres skriften lidt mindre ned til en nedre grænse. Derefter vises en venlig besked: "Bagsiden er fuld. Kortere tekst passer bedre på et foto."

---

## 5. Senere faser (ikke en del af fase 1)

### 5.1 Den talende bagside
En lille afspilknap på bagsiden med en lydfil indlejret som base64-mp3: nogen fortæller om billedet med egen stemme. Knyttes til Lydfortælling og Den Talende Booklet.

### 5.2 Gem som to billeder
Knappen "Gem forside og bagside som billeder" laver `navn_forside.jpg` og `navn_bagside.jpg`, så billedet kan sendes som almindelige billeder i en besked. Bagsiden tegnes på canvas med håndskriften. Det kan genbruge Fotolabs `tegnTekst`.

### 5.3 Den usynlige bagside
Bagsidens tekst og DC-felterne skrives som XMP ind i den indlejrede JPG, uden at brugeren ser det. Så overlever teksten, hvis nogen tager billedet ud af HTML-filen. Ingen brugerflade.

### 5.4 Album-bygger med bagsider
Lysboksen i Album-bygger får vendefunktionen, så hvert billede i et album kan have en bagside. Det kræver en udvidelse af `albumData` med et valgfrit felt pr. billede. Det **skal** ske bagudkompatibelt, så eksisterende albums virker uændret.

### 5.5 Takkede kanter
Valgfri takket kant på forsiden, som fotos fra 1950'erne og 60'erne.

### 5.6 Print
Udskriv forside og bagside på hver sin side af arket (duplex).

---

## 6. Accepttest for fase 1

- [ ] En person, der ikke kender formatet, opdager bagsiden uden at få det at vide.
- [ ] Vending virker med mus, tryk, Enter og mellemrum.
- [ ] Ingen animation, når "reducer bevægelse" er slået til i styresystemet.
- [ ] En skærmlæser læser bagsidens tekst op, når kortet er vendt.
- [ ] Filen virker offline og uden internet, også håndskriften.
- [ ] Filen åbner og kan vendes i Safari på iPhone og i Chrome på Android.
- [ ] Stående og liggende billeder vises begge i deres egne proportioner.
- [ ] Byggeren kan åbne en gemt fil og redigere den igen (`builder-source-data`).
- [ ] Tjeklisten i Builder-standarden er gennemgået.

---

## 7. Åbne spørgsmål

1. **Navnet.** Vendebillede, Bagside, Det vendbare billede eller noget fjerde?
2. **Bagsidens form.** Kun fri tekst, eller faste linjer (hvem, hvor, hvornår) plus fri tekst?
3. **Deling på telefoner.** Er link til en fil på webhotellet nok, eller skal 5.2 med i fase 1?
4. **Forhold til Fotolab.** Skal Fotolab på sigt kunne sende et billede direkte videre til byggeren?
5. **Bagsidens tekst og DC.** Skal bagsidens tekst automatisk kunne bruges som `dc.description`, eller skal de holdes adskilt?
