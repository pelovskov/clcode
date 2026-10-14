# clcode
Det er en fornuftig plan. Et nyt repo ved siden af dit gamle setup betyder, at du kan eksperimentere uden risiko. 

**Køreplan**

1. **Opret et nyt repo, gerne privat til at begynde med.** Det kan du gøre direkte i GitHub Desktop. Kald det noget i retning af `builders`. Når du er tryg, kan du gøre det offentligt og slå GitHub Pages til.

2. **Læg fundamentet.** Læg Builder-standarden ind som en Markdown-fil, fx `BUILDER-STANDARD.md`, og skriv en kort `README.md`, der forklarer, hvad repoet er. Overvej også en fil, der hedder `CLAUDE.md`. Den læser Claude Code automatisk, hver gang den starter. Her kan du skrive de faste regler: "Alle builders er singlefile-HTML. Følg BUILDER-STANDARD.md. Skriv på dansk. Ingen eksterne afhængigheder." Så slipper du for at gentage dig selv i hver samtale. Jeg kan lave et udkast til den, hvis du vil.

3. **Forbind Claude Code i browseren til repoet.** Gå ind på Claude Code via claude.ai, forbind din GitHub-konto og vælg det nye repo. Præcis hvordan forbindelsen foregår, kan ændre sig, så følg vejledningen på skærmen, eller slå den op i Claudes dokumentation.

4. **Start med en ufarlig opgave.** Bed den fx om at forklare, hvad der ligger i repoet, og foreslå forbedringer til README'en. Du lærer, hvordan det føles, uden at noget kan gå galt.

5. **Læg den første builder ind og bed om en gennemgang først.** Du kan skrive: "Gennemgå denne builder mod standarden og giv mig en liste over afvigelser. Ret ikke noget endnu." Så ser du, hvordan den tænker, før den rører din kode.

6. **Lad den rette, og gennemse i GitHub Desktop.** Ændringerne lander typisk på en separat gren. Hent dem ind i GitHub Desktop, se præcis hvad der er ændret, test filen i browseren, og godkend eller kassér.

7. **Tag den næste builder**, når første runde sidder. Her får du glæde af, at standarden er skrevet ned. Den samme opskrift virker igen.

**Gode vaner at starte med**

- Bed om én ting ad gangen, og hold ændringerne små, så de er lette at gennemse.
- Kasser frit. Ligner en ændring ikke det, du ville have, så smid den væk og prøv igen med en tydeligere besked.
- Husk Fetch/Pull i GitHub Desktop, før du går i gang på den anden maskine.
- Behold dit gamle setup ved siden af, indtil du føler dig sikker. Det har du allerede planlagt.

Første runde med trin 1 til 4 kan nok klares på en rolig aften. Resten kan tages i det tempo, der passer dig, og det kan være en god anledning til at få Bertram forbi og se over skulderen.

----
Om claude.md

Her er udkastet til `CLAUDE.md`. Filen hedder med småt "md", fordi det er skrivemåden, Claude Code leder efter, og den skal ligge i repoets rod (øverst i mappen).

Jeg har bygget den på det, du har fortalt om dine værktøjer: singlefile-HTML, ingen eksterne afhængigheder, langtidsholdbarhed, tilgængelighed og dansk sprog. Jeg har ikke set selve Builder-standarden, så filen henviser bare til `BUILDER-STANDARD.md` i stedet for at gentage dens indhold. Det er også bedst, for så har du kun ét sted at rette.

Et par ting at tjekke, før du bruger den:

- **Filnavne uden æ, ø og å** er min antagelse for at undgå problemer på nettet. Passer det ikke til din praksis, så ret linjen.
- **WCAG 2.2 AA** står der, fordi du arbejder med det på Røde Kors' hjemmeside. Fjern det, hvis det ikke gælder for byggerne.
- **"Gennemgå før du retter"** og **"Ingen sletning uden at spørge"** gør Claude Code forsigtig. Når du bliver mere fortrolig, kan du løsne dem.

Filen er tænkt som levende. Opdager du en gentagen misforståelse, så skriv en ny regel nederst, så skal du kun sige det én gang.