<?php
/* Copyright (C) 2026 Siliteo
 *
 * Dolisirene - main search page
 */

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

if (!isModEnabled('dolisirene')) accessforbidden();
if (!$user->hasRight('dolisirene', 'use')) accessforbidden();

$langs->loadLangs(array("companies", "dolisirene@dolisirene"));

$title = $langs->trans("DolisireneSearch");
llxHeader('', $title, '', '', 0, 0, array('/dolisirene/js/dolisirene.js'), array('/dolisirene/css/dolisirene.css'));

print load_fiche_titre($title, '', 'fa-building');

print '<div class="dolisirene-intro opacitymedium">'.$langs->trans("DolisireneIntro").'</div>';

print '<div class="dolisirene-searchbar">';
print '<input type="text" id="dolisirene-q" class="flat" placeholder="'.dol_escape_htmltag($langs->trans("DolisireneQueryPlaceholder")).'" autofocus>';
print '<input type="text" id="dolisirene-cp" class="flat" maxlength="5" placeholder="'.dol_escape_htmltag($langs->trans("ZipCode")).'" style="width:80px">';
print '<input type="text" id="dolisirene-city" class="flat" placeholder="'.dol_escape_htmltag($langs->trans("Town")).'">';
print '<button type="button" id="dolisirene-btn" class="button">'.$langs->trans("Search").'</button>';
print '</div>';

print '<div id="dolisirene-status" class="dolisirene-status"></div>';
print '<div id="dolisirene-results" class="dolisirene-results"></div>';

$token = newToken();
$ajaxUrl = dol_buildpath('/dolisirene/ajax/search.php', 1);
$createUrl = DOL_URL_ROOT.'/societe/card.php';

print '<script>'."\n";
print 'window.dolisirene_config = '.json_encode(array(
    'ajax_url' => $ajaxUrl,
    'create_url' => $createUrl,
    'token' => $token,
    'labels' => array(
        'searching' => $langs->transnoentities("DolisireneSearching"),
        'no_results' => $langs->transnoentities("DolisireneNoResults"),
        'error' => $langs->transnoentities("Error"),
        'create' => $langs->transnoentities("DolisireneCreate"),
        'siret' => $langs->transnoentities("ProfId1FR"),
        'tva' => $langs->transnoentities("VATIntra"),
        'ape' => $langs->transnoentities("ProfId2FR"),
        'inactive' => $langs->transnoentities("DolisireneInactive"),
        'min_chars' => $langs->transnoentities("DolisireneMinChars"),
    ),
    'mode' => 'create',
)).';'."\n";
print '</script>';

llxFooter();
$db->close();
