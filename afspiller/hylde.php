<?php
/* SFB-afspiller · historiehylden
   Laver en liste over zip-filerne i mappen hylde/ ved siden af denne fil.
   Afspilleren læser selv titel, billede og beskrivelse inde i zip-filerne,
   så her står kun filnavn, størrelse og tidspunkt.
   Læg nye historier på hylden ved at lægge zip-filen i mappen hylde/ med FTP.
   Filnavne: små bogstaver, tal og bindestreger, fx syd-for-banen-fortaeller.zip. */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Content-Type-Options: nosniff');

$mappe = __DIR__ . '/hylde';
$liste = [];
foreach (glob($mappe . '/*.zip') ?: [] as $sti) {
    $navn = basename($sti);
    if (!preg_match('/^[a-z0-9][a-z0-9._-]*\.zip$/', $navn) || !is_file($sti)) continue;
    $liste[] = ['fil' => 'hylde/' . $navn, 'bytes' => filesize($sti), 'tid' => filemtime($sti)];
}
// Nyeste først.
usort($liste, function ($a, $b) { return $b['tid'] <=> $a['tid']; });
echo json_encode(['hylde' => $liste], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
