<?php
/* Copyright (C) 2026 Siliteo
 *
 * Hook (context 'all'): inject autocomplete on societe/card.php create/edit
 * + "Completer via Sirene" button on existing societe cards.
 */

class ActionsDolisirene
{
    public $db;
    public $error = '';
    public $errors = array();
    public $resprints = '';
    public $results = array();

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Add "Completer via Sirene" button on third-party card toolbar.
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs;

        if (!isModEnabled('dolisirene')) return 0;
        if (empty($user->id) || !$user->hasRight('dolisirene', 'use')) return 0;
        if (!is_object($object) || empty($object->id)) return 0;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/societe/card.php') === false) return 0;

        $langs->load("dolisirene@dolisirene");

        $url = dol_buildpath('/dolisirene/complete.php', 1).'?socid='.((int) $object->id);
        $missing = array();
        if (empty($object->siret) && empty($object->idprof1)) $missing[] = 'SIRET';
        if (empty($object->tva_intra)) $missing[] = 'TVA';
        if (empty($object->ape) && empty($object->idprof3)) $missing[] = 'APE';

        $label = $langs->trans("DolisireneCompleteBtn");
        if (!empty($missing)) $label .= ' ('.implode(', ', $missing).')';

        $this->resprints = '<a class="butAction" href="'.dol_escape_htmltag($url).'" title="'.dol_escape_htmltag($langs->trans("DolisireneCompleteTooltip")).'">'.dol_escape_htmltag($label).'</a>';

        return 0;
    }

    /**
     * Inject autocomplete JS on societe/card.php create/edit page (fires at end of every page).
     */
    public function beforeBodyClose($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs, $conf;

        if (!isModEnabled('dolisirene')) return 0;
        if (empty($user->id) || !$user->hasRight('dolisirene', 'use')) return 0;

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/societe/card.php') === false) return 0;
        if (strpos($uri, 'action=create') === false && strpos($uri, 'action=edit') === false) return 0;

        $langs->load("dolisirene@dolisirene");

        $ajaxUrl = dol_buildpath('/dolisirene/ajax/search.php', 1);
        $token = newToken();

        $cfg = array(
            'ajax_url' => $ajaxUrl,
            'token' => $token,
            'mode' => 'autocomplete',
            'country_id' => (int) getDolGlobalInt('DOLISIRENE_COUNTRY_ID', 1),
            'labels' => array(
                'searching' => $langs->transnoentities("DolisireneSearching"),
                'no_results' => $langs->transnoentities("DolisireneNoResults"),
                'source' => $langs->transnoentities("DolisireneSource"),
                'apply' => $langs->transnoentities("DolisireneApply"),
            ),
        );

        $out = "\n<!-- Dolisirene autocomplete v1.2 -->\n";
        $out .= '<script>window.dolisirene_autocomplete = '.json_encode($cfg).';</script>'."\n";
        $jsPath = dol_buildpath('/dolisirene/js/autocomplete.js', 0);
        $jsUrl  = dol_buildpath('/dolisirene/js/autocomplete.js', 1);
        if (@file_exists($jsPath)) {
            $out .= '<script src="'.$jsUrl.'?v='.@filemtime($jsPath).'"></script>'."\n";
        }
        $out .= "<!-- End Dolisirene -->\n";

        $this->resprints = $out;
        return 0;
    }
}
