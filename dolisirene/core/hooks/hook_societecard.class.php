<?php
/* Copyright (C) 2026 Siliteo
 *
 * Hook: add "Complete via Sirene" button on societe card
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
     * Add button on third-party card toolbar when data is incomplete.
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs;

        if (!isModEnabled('dolisirene')) return 0;
        if (empty($user->id) || !$user->hasRight('dolisirene', 'use')) return 0;
        if (!is_object($object) || empty($object->id)) return 0;

        $ctx = $parameters['context'] ?? '';
        if (stripos($ctx, 'thirdpartycard') === false && stripos($ctx, 'societecard') === false) return 0;

        $langs->load("dolisirene@dolisirene");

        $url = dol_buildpath('/dolisirene/complete.php', 1).'?socid='.((int) $object->id);
        $missing = array();
        if (empty($object->siret) && empty($object->idprof1)) $missing[] = 'SIRET';
        if (empty($object->tva_intra)) $missing[] = 'TVA';
        if (empty($object->ape) && empty($object->idprof2)) $missing[] = 'APE';

        $label = $langs->trans("DolisireneCompleteBtn");
        if (!empty($missing)) $label .= ' ('.implode(', ', $missing).')';

        $class = !empty($missing) ? 'butAction' : 'butActionRefused';
        $this->resprints = '<a class="butAction" href="'.dol_escape_htmltag($url).'" title="'.dol_escape_htmltag($langs->trans("DolisireneCompleteTooltip")).'">'.dol_escape_htmltag($label).'</a>';

        return 0;
    }

    /**
     * Inject autocomplete on the third-party create form (societe/card.php?action=create).
     * Fires at the end of the page, in the societecard context.
     */
    public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs, $conf;

        if (!isModEnabled('dolisirene')) return 0;
        if (empty($user->id) || !$user->hasRight('dolisirene', 'use')) return 0;

        $ctx = $parameters['context'] ?? '';
        if (stripos($ctx, 'thirdpartycard') === false && stripos($ctx, 'societecard') === false) return 0;

        // Only on create/edit forms
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isCreate = (strpos($uri, 'action=create') !== false) || ($action === 'create');
        $isEdit   = (strpos($uri, 'action=edit') !== false)   || ($action === 'edit');
        if (!$isCreate && !$isEdit) return 0;

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

        $out = "\n<!-- Dolisirene autocomplete -->\n";
        $out .= '<script>window.dolisirene_autocomplete = '.json_encode($cfg).';</script>'."\n";
        $jsPath = dol_buildpath('/dolisirene/js/autocomplete.js', 0);
        $jsUrl  = dol_buildpath('/dolisirene/js/autocomplete.js', 1);
        if (@file_exists($jsPath)) {
            $out .= '<script src="'.$jsUrl.'?v='.@filemtime($jsPath).'"></script>'."\n";
        }
        $cssPath = dol_buildpath('/dolisirene/css/dolisirene.css', 0);
        $cssUrl  = dol_buildpath('/dolisirene/css/dolisirene.css', 1);
        if (@file_exists($cssPath)) {
            $out .= '<link rel="stylesheet" href="'.$cssUrl.'?v='.@filemtime($cssPath).'">'."\n";
        }

        $this->resprints = $out;
        return 0;
    }
}
