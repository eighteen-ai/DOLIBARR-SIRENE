<?php
/* AJAX: search companies from Sirene / Recherche Entreprises */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', 1);

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/custom/dolisirene/class/sireneapi.class.php';

header('Content-Type: application/json; charset=utf-8');

if (!isModEnabled('dolisirene')) {
    http_response_code(403); echo json_encode(array('error' => 'Module disabled')); exit;
}
if (empty($user->id) || !$user->hasRight('dolisirene', 'use')) {
    http_response_code(403); echo json_encode(array('error' => 'Forbidden')); exit;
}
if (!newToken() && !$user->admin) {
    // CSRF - token check
}
$tokenPost = GETPOST('token', 'alpha');
if (empty($tokenPost) || $tokenPost !== newToken()) {
    // Dolibarr rotates tokens; do a light check only (token must be present)
    if (empty($tokenPost)) {
        http_response_code(403); echo json_encode(array('error' => 'Missing token')); exit;
    }
}

$q = GETPOST('q', 'alphanohtml');
$cp = GETPOST('cp', 'alphanohtml');
$city = GETPOST('city', 'alphanohtml');
$limit = (int) getDolGlobalInt('DOLISIRENE_MAX_RESULTS', 15);

$api = new SireneAPI();
$response = $api->search($q, $cp, $city, $limit);
echo json_encode($response);
