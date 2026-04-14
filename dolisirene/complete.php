<?php
/* Copyright (C) 2026 Siliteo
 *
 * Dolisirene - complete an existing societe with Sirene data
 */

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

if (!isModEnabled('dolisirene')) accessforbidden();
if (!$user->hasRight('dolisirene', 'use')) accessforbidden();
if (!$user->hasRight('societe', 'creer')) accessforbidden();

$langs->loadLangs(array("companies", "dolisirene@dolisirene"));

$socid = GETPOSTINT('socid');
if (empty($socid)) accessforbidden();

$societe = new Societe($db);
if ($societe->fetch($socid) <= 0) accessforbidden();

$title = $langs->trans("DolisireneComplete").' - '.$societe->name;
llxHeader('', $title, '', '', 0, 0, array('/dolisirene/js/dolisirene.js'), array('/dolisirene/css/dolisirene.css'));

print load_fiche_titre($title, '', 'fa-building');

print '<div class="dolisirene-intro opacitymedium">'.$langs->trans("DolisireneCompleteIntro", $societe->name).'</div>';

print '<div class="dolisirene-searchbar">';
print '<input type="text" id="dolisirene-q" class="flat" placeholder="'.dol_escape_htmltag($langs->trans("DolisireneQueryPlaceholder")).'">';
print '<input type="text" id="dolisirene-cp" class="flat" maxlength="5" style="width:80px">';
print '<input type="text" id="dolisirene-city" class="flat">';
print '<button type="button" id="dolisirene-btn" class="button">'.$langs->trans("Search").'</button>';
print '<label style="margin-left:12px"><input type="checkbox" id="dolisirene-overwrite"> '.$langs->trans("DolisireneOverwrite").'</label>';
print '</div>';

print '<div id="dolisirene-status" class="dolisirene-status"></div>';
print '<div id="dolisirene-results" class="dolisirene-results"></div>';

$token = newToken();
$ajaxUrl = dol_buildpath('/dolisirene/ajax/search.php', 1);
$completeUrl = dol_buildpath('/dolisirene/ajax/complete.php', 1);

// Build auto-search query from the existing societe name (keep only relevant words)
$autoQ = preg_replace('/\b(SA|SAS|SARL|EURL|SASU|SCI|SNC|ASSOC|ASSOCIATION|ETS)\b/i', ' ', $societe->name);
$autoQ = trim(preg_replace('/\s+/', ' ', $autoQ));

print '<script>'."\n";
print 'window.dolisirene_config = '.json_encode(array(
    'ajax_url' => $ajaxUrl,
    'complete_url' => $completeUrl,
    'token' => $token,
    'mode' => 'complete',
    'socid' => (int) $socid,
    'autosearch' => array(
        'q' => $autoQ,
        'cp' => $societe->zip,
        'city' => $societe->town,
    ),
    'labels' => array(
        'searching' => $langs->transnoentities("DolisireneSearching"),
        'no_results' => $langs->transnoentities("DolisireneNoResults"),
        'error' => $langs->transnoentities("Error"),
        'create' => $langs->transnoentities("DolisireneCompleteBtn"),
        'completed' => $langs->transnoentities("DolisireneCompleted"),
        'siret' => $langs->transnoentities("ProfId1FR"),
        'tva' => $langs->transnoentities("VATIntra"),
        'ape' => $langs->transnoentities("ProfId2FR"),
        'inactive' => $langs->transnoentities("DolisireneInactive"),
        'min_chars' => $langs->transnoentities("DolisireneMinChars"),
    ),
)).';'."\n";
// overwrite checkbox binding
print "document.addEventListener('DOMContentLoaded',function(){var o=document.getElementById('dolisirene-overwrite');if(o)o.addEventListener('change',function(){window.dolisirene_config.overwrite=o.checked;});});\n";
print '</script>';

llxFooter();
$db->close();
