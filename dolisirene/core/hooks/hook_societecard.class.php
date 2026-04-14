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
}
