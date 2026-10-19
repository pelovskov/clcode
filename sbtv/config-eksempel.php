<?php
/* =========================================================================
   Søgeindeks · Online — indstillinger (EKSEMPEL)
   Kopiér denne fil til config.php på webhotellet, og ret i kopien.
   config.php skal ALDRIG lægges i repoet, fordi den indeholder adgangskoder.
   Dette er den eneste fil, du normalt skal rette i.
   ========================================================================= */

/* Brugere der må logge ind og lægge filer op.
   Format:  'brugernavn' => 'bcrypt-hash',
   Lav hashen med lav-hash.php (eller dit eget hash-værktøj, cost 12).
   Skriv brugernavne med små bogstaver. */
$BRUGERE = [
    // 'brugernavn' => '$2y$12$................................................',
    // 'kim'  => '$2y$12$................................................',
    // 'anne' => '$2y$12$................................................',
];

/* Hvem må bruge admin-siden (slette filer, genopbygge, ændre overskrift)?
   Tom liste = alle brugere ovenfor.  Eksempel: $ADMINS = ['kim']; */
$ADMINS = [];

/* Overskrift og undertekst, indtil de bliver ændret på admin-siden. */
$STANDARD_TITEL      = 'Søg i lokalhistorier';
$STANDARD_UNDERTITEL = '';

/* Siden lav-hash.php laver hashes til brugerlisten. Den gemmer intet.
   Sæt til false, når alle brugere er oprettet. */
$TILLAD_HASH_SIDE = true;

date_default_timezone_set('Europe/Copenhagen');
