# Projektresumé: Lokalhistorier, værktøjskassen

Status: oktober 2026

Resuméet er skrevet til idéudvikling og sparring med mennesker og med Claude. Det fortæller, hvad projektet er, hvem det er til, hvad der findes nu, og hvilke spørgsmål der er åbne. Reglerne for, *hvordan* Claude arbejder i repoet, står i `CLAUDE.md`. De tekniske detaljer står i `builder-standard.md` og `oversigt.md`.

**Sådan bruges resuméet:** Del filen med dem, du vil sparre med. Med Claude kan du vedhæfte den eller sætte teksten ind i starten af en samtale og så skrive, hvad du vil tale om.

## Idéen i én sætning

Frivillige i foreninger og grupper med historier fra Roskilde og omegn skal kunne lave flotte, holdbare formidlingsfiler (artikler, fotoalbums, lydfortællinger, e-bøger og præsentationer) uden at kunne programmere, og filerne skal kunne findes på tværs af foreningerne.

## Baggrund

**Inspirationen** kom fra oplæg og foredrag. De fleste, der har stået foran et publikum, kender det: Ingen havde tænkt på, at der skulle være internet, eller PowerPoint-præsentationen ville ikke køre på en forældet computer. Derfor er værktøjerne lavet, så albums, lydfortællinger og præsentationer kører direkte fra et USB-stik, helt uden netforbindelse og uden at installere noget. Det har imponeret mange tilhørere og er grunden til, at hele systemet bygger på singlefile og offline.

Det handler også om fremtiden. Så længe der er gode og stabile netforbindelser, bruger vi dem selvfølgelig, men intet må *afhænge* af dem. En fil, der virker i dag uden net og uden installation, har gode chancer for også at virke om mange år.

Projektet startede som "Lokalhistorie på tværs af generationer" i Syd for Banen. Det lykkedes ikke så godt, så projektet er åbnet for andre aktører. Derfor handler det nu om **lokalhistorier** og ikke kun om lokalhistorie. Som Syd for Banen siger: Lokalhistorie er ikke kun vikinger, men det, der foregår lige uden for døren. Med den drejning passer Røde Kors og Roskilde Musik lige så godt ind som de historiske foreninger.

## Hvem er det til?

- **Brugerne** er frivillige, mange af dem ældre og ikke vant til it. Alt skal være enkelt, tydeligt og let at læse.
- **Foreningerne og fællesskaberne** er i dag:
  - Syd for Banen - Lokalhistorisk Forening;
  - Historisk Samfund for Roskilde Amt;
  - Roskilde TV;
  - Røde Kors;
  - Roskilde Musik;
  - cirkler, et fællesskab for dem, der gerne vil lære nye mennesker at kende, og det nyeste.
  - Desuden kan man vælge *Privat* som udgiver.
- **Publikum** er alle med interesse for Roskildes historie.

## Sådan hænger det sammen

1. **En bygger** er en side, man åbner i browseren. Man udfylder felter, lægger billeder eller lyd ind og vælger farvetema efter forening. Ud kommer én færdig fil.
2. **Den færdige fil** kan gemmes, deles på en hjemmeside, sendes på mail og åbnes om mange år, også uden internet. Byggeren kan åbne filen igen, så den kan rettes.
3. **Metadata** (Dublin Core) ligger inde i hver fil: titel, ophav, emneord, udgiver, dato og meget mere. Det svarer til et kartotekskort i et arkiv.
4. **Søgesiderne** læser metadataene og gør filerne søgbare:
   - Søgeside · Bygger laver en søgeside til en mappe med filer;
   - Søgeindeks · Online er en fælles søgeportal på webhotellet, hvor hver forening kan få sin egen visning.
5. **Fælles emneord** (fx Musik og Arkitektur) gør det muligt at finde alt om et emne på tværs af foreningerne. Udgiveren siger, *hvem* der har lavet historien, og emneordet siger, *hvad* den handler om.

## Hvad findes nu?

**11 byggere:**

| Bygger | Laver |
|---|---|
| Sidebygger | artikelsider om gader, steder og mennesker |
| Album | billedalbum, hvor billederne kan ses i stort format |
| Enkeltbillede | ét billede i høj kvalitet med tekst |
| Før og Nu | to billeder fra samme sted med skyder |
| Bogbygger | e-bog med kapitler |
| Lydfortælling | lydafspiller med billede og tekst |
| Mediekort | kort til video, podcast eller et talende billede |
| Præsentation | billedshow til foredrag |
| Henvisning | "skilt", der sender videre til en side på nettet |
| Samling | forside, der binder flere filer sammen |
| Piwigo Album | album hentet fra foreningens billedgalleri |

**Værktøjer:** Feltoptager (optag lyd i marken), Lydlab og Fotolab (gør lyd og billeder klar), Metadata-hjælper (lader en AI foreslå metadata og kontrollerer svaret), Musikpakke, Skyggeramme, Billedarkiv (ordner billeder i albums) og et værktøj til sikre adgangskoder.

**Fælles regler:** Builder-standarden (version 2.13) sørger for, at alle byggere ser ens ud, har de samme syv farvetemaer og de samme udgivere og håndterer metadata ens.

## Fri udgave til alle andre

Den fælles portal er for foreningerne. Enkeltpersoner, skoleklasser, ungdomsskolen og andre institutioner skal også kunne lave historier, album og lydfortællinger, men uden at det hele kommer ind i den fælles samling. Til dem er der **Lokalhistorier · fri udgave** i mappen `fri/`, med sin egen forside:

- de samme byggere, plus Samling og Søgeside, så en skoleklasse kan samle sine ting i en mappe med egen forside og søgeside;
- udgiveren skrives frit, fx "7.b, Roskilde Lille Skole";
- kun to farvetemaer, Vinter og Sommer.

Den frie udgave laves automatisk ud fra foreningernes byggere, så forbedringer følger med. Der er huskesedler til de vigtigste byggere, som kan printes på én side, og manuskripter til korte screencasts i mappen `vejledninger/`.

## Det, projektet holder fast i

- **Én fil pr. værktøj og pr. resultat.** Intet skal installeres, og intet afhænger af fremmede tjenester.
- **Holdbarhed.** Filerne skal kunne åbnes om mange år, så de bruger almindelig HTML.
- **Tilgængelighed.** Stor skrift, høj kontrast og tydelige knapper (WCAG 2.2 AA).
- **Metadata følger altid med**, så arbejdet kan findes og bruges igen.
- **Sikkerhed.** Repoet er offentligt, så adgangskoder og serverdata holdes ude af det.

## Åbne spørgsmål og idéer

**Næste skridt, der allerede er nævnt:**

- **Emneordslisten:** aftales med de andre foreninger og bygges senere ind i Metadata-hjælper, så stavemåden altid bliver ens.
- **Mørk tilstand:** Orange og rød tekst på mørk baggrund er lidt svag i de farvede temaer. Det kan rettes for alle temaer på én gang.
- **Roskilde Musik i søgeportalen:** mangler hjemmeside og rettighedstekst, når foreningen har bestemt dem.
- **Byggere, der er beskrevet i standarden, men ikke lavet endnu:** Vejledningsbygger og PWA-bygger.

**Gode spørgsmål til sparring:**

- Hvordan får vi flere foreninger og frivillige til at bruge værktøjerne? Hvad skal en introduktion eller et kursus indeholde?
- Hvilke emner går igen på tværs af foreningerne, og hvilke emneord mangler på listen?
- Hvordan bliver søgeportalen et fælles sted, som foreningerne har lyst til at linke til?
- Hvilke typer historier kan vi ikke lave i dag? Er der brug for en ny bygger?
- Hvordan sikrer vi, at filerne bliver bevaret på lang sigt, fx med backup og aflevering til et arkiv?

## Filer, der er gode at kende

| Fil | Indhold |
|---|---|
| `projekt-resume.md` | dette resumé |
| `oversigt.md` | alle byggere og værktøjer med versioner |
| `builder-standard.md` | de fælles regler for byggerne |
| `emneord.md` | de fælles emneord |
| `CLAUDE.md` | husregler for samarbejdet med Claude |
| `bygger-dash.html` | forside med links til det hele |
| `sbtv/LAES-MIG.md` | vejledning til søgeportalen på webhotellet |
