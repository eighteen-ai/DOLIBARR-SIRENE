<?php
/* AJAX: complete an existing societe with data from a selected Sirene result */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/dolisirene/class/sireneapi.class.php';

header('Content-Type: application/json; charset=utf-8');

if (!isModEnabled('dolisirene')) {
    http_response_code(403); echo json_encode(array('error' => 'Module disabled')); exit;
}
if (empty($user->id) || !$user->hasRight('dolisirene', 'use') || !$user->hasRight('societe', 'creer')) {
    http_response_code(403); echo json_encode(array('error' => 'Forbidden')); exit;
}

$tokenPost = GETPOST('token', 'alpha');
if (empty($tokenPost)) {
    http_response_code(403); echo json_encode(array('error' => 'Missing token')); exit;
}

$socid = GETPOSTINT('socid');
$siret = preg_replace('/[^0-9]/', '', GETPOST('siret', 'alphanohtml'));
$overwrite = GETPOSTINT('overwrite') ? true : false;

if (empty($socid) || empty($siret) || strlen($siret) !== 14) {
    echo json_encode(array('error' => 'Invalid parameters')); exit;
}

$siren = substr($siret, 0, 9);
$api = new SireneAPI();
$search = $api->search($siren, '', '', 5);
$match = null;
foreach ($search['results'] as $r) {
    if ($r['siret'] === $siret || $r['siren'] === $siren) { $match = $r; break; }
}
if (!$match) {
    echo json_encode(array('error' => 'Entreprise non trouvee dans Sirene')); exit;
}

$societe = new Societe($db);
if ($societe->fetch($socid) <= 0) {
    echo json_encode(array('error' => 'Tiers introuvable')); exit;
}

$updated = array();
$fields = array(
    'siret' => $match['siret'],
    'siren' => $match['siren'],
    'tva_intra' => $match['tva_intra'],
    'ape' => $match['ape'],
    'forme_juridique_code' => $match['legal_form'],
    'address' => trim($match['address'].(!empty($match['address2']) ? "\n".$match['address2'] : '')),
    'zip' => $match['postal_code'],
    'town' => $match['city'],
);

foreach ($fields as $prop => $value) {
    if (empty($value)) continue;
    if (!property_exists($societe, $prop)) continue;
    if ($overwrite || empty($societe->$prop)) {
        $societe->$prop = $value;
        $updated[] = $prop;
    }
}
if (empty($societe->country_id)) {
    $societe->country_id = (int) getDolGlobalInt('DOLISIRENE_COUNTRY_ID', 1);
    $updated[] = 'country_id';
}

if (empty($updated)) {
    echo json_encode(array('ok' => true, 'updated' => array(), 'message' => 'Rien a mettre a jour'));
    exit;
}

$result = $societe->update($socid, $user);
if ($result <= 0) {
    echo json_encode(array('error' => $societe->error ?: 'Echec mise a jour')); exit;
}

echo json_encode(array('ok' => true, 'updated' => $updated));
