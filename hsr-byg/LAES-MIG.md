# Byggere til Historisk Samfund for Roskilde Amt

Mappen indeholder tre byggere, der er lavet til Historisk Samfunds hjemmeside og CMS. De er faste udgaver: De bliver ikke opdateret, så de sider, I laver, altid kan åbnes og rettes igen med de samme byggere.

## Byggerne

| Bygger | Fil | Laver | Version | Filformat |
|---|---|---|---|---|
| Sidebygger | `side-bygger.html` | artikelside om gader, steder og mennesker | 1.5 | 1 |
| Album · Bygger | `album-bygger.html` | billedalbum, hvor billederne kan klikkes store | 3.1 | 3 |
| Mediekort · Bygger | `mediekort-bygger.html` | kort til en video, en podcast eller et talende billede | 1.3 | 1 |

Byggerne er de samme som i fællesskabets værktøjskasse, med to forskelle:

- **Udseendet er altid "Historisk Samfund".** De andre farvetemaer er fjernet.
- **Udgiveren er altid "Historisk Samfund for Roskilde Amt".** Identifikatoren begynder derfor altid med `HSR`, fx `HSR-side-domkirkens-taarne-2026`.

## Sådan bruges de

1. Åbn byggeren i en browser, fx Chrome, Edge, Safari eller Firefox. Der skal ikke installeres noget, og den virker uden internet.
2. Udfyld felterne, og gem den færdige side.
3. Læg siden op i CMS'et.

**Vil du rette en side, der allerede er lagt op?** Åbn den samme bygger, og vælg siden under "Indlæs". Alt kommer med: tekst, billeder og oplysninger.

## Gode regler

- **Ret altid sider med byggerne i denne mappe.** Så passer byggere og sider altid sammen.
- **Hent ikke nyere byggere andre steder fra** uden at tjekke filformatet. En bygger kan ikke åbne sider med et ældre filformat end sit eget.
- **Indlæser du en side, der er lavet et andet sted,** fx med et andet farvetema eller en anden udgiver, bliver den lavet om til Historisk Samfunds udseende og udgiver. Byggeren skriver det i beskeden, når siden indlæses.
- **Tag backup** af både byggerne og de færdige sider.

## Hvor kommer de fra?

Byggerne er lavet i fællesskabets værktøjskasse, GitHub-repoet `pelovskov/clcode`, i oktober 2026 efter Builder-standard version 2.11. Siderne har samme filformat som fællesskabets byggere, så de kan også åbnes dér og lægges i den fælles søgeportal for lokalhistorier.
