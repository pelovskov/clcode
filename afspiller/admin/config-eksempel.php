<?php
/* =========================================================================
   SFB-afspiller · adminsiden — indstillinger (EKSEMPEL)
   Kopiér denne fil til config.php i samme mappe på webhotellet, og ret i kopien.
   config.php skal ALDRIG lægges i repoet, fordi den indeholder adgangskoder.
   ========================================================================= */

/* De to brugere, der må passe hylden.
   Format:  'brugernavn' => 'bcrypt-hash',
   Lav hashen med tools/password-hash.html på din egen computer (cost 12).
   Skriv brugernavne med små bogstaver. */
$BRUGERE = [
    // 'pel'  => '$2y$12$................................................',
    // 'anne' => '$2y$12$................................................',
];

date_default_timezone_set('Europe/Copenhagen');
