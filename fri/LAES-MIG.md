# Lokalhistorier · fri udgave

Mappen er en fri udgave af byggerne og værktøjerne. Den er til enkeltpersoner, skoleklasser, ungdomsskolen og andre institutioner, som vil lave deres egne historier, album, lydfortællinger og meget mere.

Start med at åbne **`index.html`**. Den er forsiden med links til det hele.

## Hvad er forskellen fra foreningernes byggere?

Den frie udgave er lavet ud fra de samme byggere, som foreningerne i den fælles portal bruger, med disse forskelle:

- **Udgiveren skriver man selv,** fx "7.b, Roskilde Lille Skole" eller sit eget navn. Der er ingen liste over foreninger.
- **Der er to farvetemaer,** Vinter (blå) og Sommer (grøn), begge med lys og mørk tilstand.
- **Identifikatoren har intet foreningspræfiks,** fx `album-sommerlejren-2026`.
- **Emneordene er dem, man selv skriver.** Udgiveren sættes ikke ind som første emneord, og de fælles emneord er ikke et krav.
- **Foreningernes særlige ting er fjernet,** fx Sidebyggerens faste serier og foreningernes farver.

Filformatet er det samme. En fil fra den frie udgave kan derfor åbnes i foreningernes byggere og omvendt. Den får så det andet sted standardtemaet.

## Indhold

| Hvad | Fil |
|---|---|
| Forside | `index.html` |
| Sidebygger, Album, Enkeltbillede, Før og Nu, Bog, Lydfortælling, Mediekort, Præsentation | `…-bygger.html` |
| Samling · Bygger, som binder 2–6 filer sammen | `samling-bygger.html` |
| Søgeside · Bygger, som laver en søgeside til en mappe | `soegeside-bygger.html` |
| Feltoptager, Lydlab, Fotolab og Skyggeramme | `feltoptager.html`, `lydlab.html`, `fotolab.html`, `skyggeramme.html` |

Hele mappen kan kopieres til et USB-stik eller lægges på en hjemmeside. Alt virker uden internet.

## Til den, der vedligeholder

Byggerne i mappen laves af opskriften **`lav-fri-udgave.py`** ud fra de almindelige byggere i repoets rod og værktøjerne i `tools/`. Ret aldrig i de lavede filer i hånden. Ret i den almindelige bygger eller i opskriften, og kør opskriften igen:

```
python3 fri/lav-fri-udgave.py
```

Kun `index.html` og `LAES-MIG.md` er skrevet i hånden. Opskriften stopper med en fejlbesked, hvis en almindelig bygger er ændret, så den ikke længere passer. Så skal opskriften rettes til.
