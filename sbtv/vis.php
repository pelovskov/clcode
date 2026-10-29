<?php
/* =========================================================================
   Søgeindeks · Online — åbn en fil og tæl klikket
   Søgesiden og forsiden linker hertil: vis.php?f=filnavn&hjem=…
   Siden lægger én til tælleren og sender straks videre til filen (eller
   til henvisningens adresse). Læseren mærker ingenting.
   Kun filer, der står i søgeindekset, kan åbnes herfra, så siden kan ikke
   bruges til at sende folk videre til fremmede adresser.
   ========================================================================= */

require __DIR__ . '/faelles.php';

header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');

$navn = (string)($_GET['f'] ?? '');
$poster = hent_indeks()['poster'];
if ($navn === '' || !isset($poster[$navn])) {
    header('Location: index.php', true, 302);
    exit;
}
$p = $poster[$navn];

/* Henvisninger fører til deres egen adresse, hvis den er http(s). */
$link = (string)($p['link'] ?? '');
if ($link !== '' && !preg_match('#^https?://#i', $link)) {
    header('Location: index.php', true, 302);
    exit;
}

tael_klik($navn);

if ($link !== '') {
    header('Location: ' . $link, true, 302);
    exit;
}

/* ?hjem= sendes med til filen, så dens Hjem-knap fører tilbage. Kun en sti
   på samme websted (begynder med én skråstreg) er tilladt. */
$hjem = (string)($_GET['hjem'] ?? '');
$til = 'filer/' . rawurlencode($navn);
if (preg_match('#^/(?![/\\\\])[^\s]*$#', $hjem)) $til .= '?hjem=' . rawurlencode($hjem);
header('Location: ' . $til, true, 302);
