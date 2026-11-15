<?php
/* =========================================================================
   Søgeindeks · Online — en tilfældig historie
   tilfaeldig.php                  en tilfældig historie fra hele søgeindekset
   tilfaeldig.php?forening=sfb     kun fra én forening (nøglen fra data/foreninger.json)
   tilfaeldig.php?type=Image       kun én slags (Text, Image, Sound, MovingImage …)
   De to kan bruges sammen. Siden vælger en historie og sender straks videre
   gennem vis.php, så klikket tælles i statistikken, og historiens Hjem-knap
   fører tilbage til søgesiden for den samme forening.
   Bruges fx som en knap på en forenings hjemmeside: «En tilfældig historie».
   Kun filer, der står i søgeindekset, kan vælges, så siden kan ikke bruges
   til at sende folk videre til fremmede adresser.
   ========================================================================= */

require __DIR__ . '/faelles.php';

/* Hvert tryk skal give en ny historie, så intet må gemmes undervejs. */
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');

$foreninger = hent_foreninger();
$noegle = strtolower(trim((string)($_GET['forening'] ?? '')));
if (!isset($foreninger[$noegle])) $noegle = '';
$udgiver = $noegle !== '' ? $foreninger[$noegle]['udgiver'] : '';

$type = trim((string)($_GET['type'] ?? ''));
if (!preg_match('/^[A-Za-z]{1,30}$/', $type)) $type = '';

/* Søgesiden for den samme forening: her ender man, hvis der ingen historier er,
   og hertil fører historiens Hjem-knap. */
$soeg = 'index.php' . ($noegle !== '' ? '?forening=' . rawurlencode($noegle) : '');

$kandidater = [];
foreach (hent_indeks()['poster'] as $navn => $p) {
    if (!is_array($p)) continue;
    if ($udgiver !== '' && mb_strtolower(trim((string)($p['publisher'] ?? '')), 'UTF-8') !== mb_strtolower($udgiver, 'UTF-8')) continue;
    if ($type !== '' && strcasecmp(trim((string)($p['type'] ?? '')), $type) !== 0) continue;
    /* Henvisninger uden en almindelig webadresse kan ikke åbnes. */
    $link = (string)($p['link'] ?? '');
    if ($link !== '' && !preg_match('#^https?://#i', $link)) continue;
    $kandidater[] = (string)$navn;
}

if (!$kandidater) {
    header('Location: ' . $soeg, true, 302);
    exit;
}

$valgt = $kandidater[random_int(0, count($kandidater) - 1)];

/* Hjem-adressen skal være en sti på samme websted, fx /over/sbtv/index.php?forening=sfb */
$mappe = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$hjem = $mappe . '/' . $soeg;

header('Location: vis.php?f=' . rawurlencode($valgt) . '&hjem=' . rawurlencode($hjem), true, 302);
