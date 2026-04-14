<?php
/* Copyright (C) 2026 Siliteo */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

if (!$user->admin) accessforbidden();

$langs->loadLangs(array("admin", "dolisirene@dolisirene"));

$action = GETPOST('action', 'aZ09');

if ($action === 'update' && GETPOST('token') === newToken()) {
    dolibarr_set_const($db, 'DOLISIRENE_MAX_RESULTS', (int) GETPOST('DOLISIRENE_MAX_RESULTS', 'int'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'DOLISIRENE_COUNTRY_ID', (int) GETPOST('DOLISIRENE_COUNTRY_ID', 'int'), 'chaine', 0, '', $conf->entity);
    $apiUrl = GETPOST('DOLISIRENE_API_URL', 'alphanohtml');
    if (!empty($apiUrl)) dolibarr_set_const($db, 'DOLISIRENE_API_URL', $apiUrl, 'chaine', 0, '', $conf->entity);
    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    header("Location: ".$_SERVER['PHP_SELF']); exit;
}

llxHeader('', $langs->trans("DolisireneSetup"));
print load_fiche_titre($langs->trans("DolisireneSetup"), '', 'fa-building');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("DolisireneApiUrl").'</td>';
print '<td><input type="text" name="DOLISIRENE_API_URL" class="flat quatrevingtpercent" value="'.dol_escape_htmltag(getDolGlobalString('DOLISIRENE_API_URL', 'https://recherche-entreprises.api.gouv.fr/search')).'"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("DolisireneMaxResults").'</td>';
print '<td><input type="number" name="DOLISIRENE_MAX_RESULTS" class="flat" min="1" max="25" value="'.((int) getDolGlobalInt('DOLISIRENE_MAX_RESULTS', 15)).'"></td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("DolisireneCountryId").'</td>';
print '<td><input type="number" name="DOLISIRENE_COUNTRY_ID" class="flat" min="1" value="'.((int) getDolGlobalInt('DOLISIRENE_COUNTRY_ID', 1)).'"></td></tr>';

print '</table>';

print '<div class="center" style="margin-top:20px">';
print '<button type="submit" class="button">'.$langs->trans("Save").'</button>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
