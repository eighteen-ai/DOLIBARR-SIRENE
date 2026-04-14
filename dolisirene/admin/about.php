<?php
/* Copyright (C) 2026 Siliteo */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

if (!$user->admin) accessforbidden();
$langs->loadLangs(array("admin", "dolisirene@dolisirene"));

llxHeader('', 'Dolisirene - About');
print load_fiche_titre('Dolisirene', '', 'fa-building');
print '<div class="info">';
print '<p><strong>Dolisirene</strong> v1.0.0 - '.$langs->trans("DolisireneAbout").'</p>';
print '<p>'.$langs->trans("DolisireneAboutSource").' <a href="https://recherche-entreprises.api.gouv.fr/" target="_blank">recherche-entreprises.api.gouv.fr</a></p>';
print '<p>Siliteo - <a href="https://www.siliteo.fr" target="_blank">siliteo.fr</a></p>';
print '</div>';
llxFooter();
$db->close();
