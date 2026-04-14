<?php
/* Copyright (C) 2026 Siliteo
 *
 * Module descriptor for Dolisirene - Sirene/data.gouv company lookup
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modDolisirene extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        $this->numero = 500270;
        $this->rights_class = 'dolisirene';
        $this->family = "interface";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Recherche et creation de tiers depuis l'API officielle Sirene (data.gouv.fr)";
        $this->descriptionlong = "Recherche des entreprises francaises par nom via l'API Recherche Entreprises (data.gouv.fr), cree un tiers pre-rempli (SIRET, TVA intra, adresse, NAF) et complete les fiches tiers existantes lorsque des informations manquent.";
        $this->editor_name = 'Siliteo';
        $this->editor_url = 'https://www.siliteo.fr';
        $this->version = '1.1.0';
        $this->const_name = 'MAIN_MODULE_DOLISIRENE';
        $this->picto = 'fa-building';

        $this->module_parts = array(
            'hooks' => array('societecard'),
            'triggers' => 0,
            'models' => 0,
            'css' => array('/dolisirene/css/dolisirene.css'),
            'js' => array(),
        );

        $this->dirs = array();

        $this->config_page_url = array("setup.php@dolisirene");

        $this->hidden = false;
        $this->depends = array('modSociete');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array("dolisirene@dolisirene");

        $this->const = array(
            0 => array('DOLISIRENE_API_URL', 'chaine', 'https://recherche-entreprises.api.gouv.fr/search', 'Endpoint API Recherche Entreprises', 0, 'current', 1),
            1 => array('DOLISIRENE_MAX_RESULTS', 'int', '15', 'Nombre max de resultats', 0, 'current', 1),
            2 => array('DOLISIRENE_COUNTRY_ID', 'int', '1', 'ID pays par defaut (1=France)', 0, 'current', 1),
        );

        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = $this->numero + 1;
        $this->rights[$r][1] = 'Rechercher et creer un tiers depuis Sirene';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'use';
        $r++;

        $this->rights[$r][0] = $this->numero + 2;
        $this->rights[$r][1] = 'Configurer le module Dolisirene';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'setup';
        $r++;

        $this->menu = array();
        $this->menu[0] = array(
            'fk_menu' => 'fk_mainmenu=companies',
            'type' => 'left',
            'titre' => 'DolisireneSearch',
            'mainmenu' => 'companies',
            'leftmenu' => 'dolisirene_search',
            'url' => '/custom/dolisirene/search.php',
            'langs' => 'dolisirene@dolisirene',
            'position' => 101,
            'enabled' => '$conf->dolisirene->enabled',
            'perms' => '$user->hasRight("dolisirene","use")',
            'target' => '',
            'user' => 2,
        );

        $this->tabs = array();
    }

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
