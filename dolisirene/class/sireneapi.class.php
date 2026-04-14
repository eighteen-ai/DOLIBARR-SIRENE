<?php
/* Copyright (C) 2026 Siliteo
 *
 * SireneAPI - wrapper for the public "Recherche Entreprises" API
 * https://recherche-entreprises.api.gouv.fr/ (no API key, 7 req/s)
 */

class SireneAPI
{
    public $error = '';
    private $baseUrl;
    private $timeout = 10;

    public function __construct()
    {
        $this->baseUrl = getDolGlobalString('DOLISIRENE_API_URL', 'https://recherche-entreprises.api.gouv.fr/search');
    }

    /**
     * Search for companies by name, with optional postal code / city filter.
     *
     * @param string $query          Company name or partial name
     * @param string $postalCode     Optional postal code filter
     * @param string $city           Optional city filter
     * @param int    $limit          Max results
     * @return array{results: array, error: string}
     */
    public function search($query, $postalCode = '', $city = '', $limit = 15)
    {
        $query = trim($query);
        if (dol_strlen($query) < 2) {
            return array('results' => array(), 'error' => 'Query too short');
        }

        $params = array(
            'q' => $query,
            'per_page' => max(1, min(25, (int) $limit)),
            'page' => 1,
        );
        if (!empty($postalCode)) {
            $params['code_postal'] = preg_replace('/[^0-9]/', '', $postalCode);
        }
        // City is applied client-side on results (API filters by INSEE code only)

        $url = $this->baseUrl.'?'.http_build_query($params);
        $raw = $this->httpGet($url);
        if ($raw === false) {
            return array('results' => array(), 'error' => $this->error);
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['results'])) {
            return array('results' => array(), 'error' => 'Invalid API response');
        }

        $out = array();
        foreach ($data['results'] as $r) {
            $item = $this->normalizeResult($r);
            if ($item === null) continue;
            if (!empty($city) && stripos($item['city'], $city) === false) continue;
            $out[] = $item;
        }

        return array('results' => $out, 'error' => '');
    }

    /**
     * Normalize an API result to a compact structure usable by the UI.
     */
    private function normalizeResult($r)
    {
        $siege = $r['siege'] ?? array();
        if (empty($siege)) return null;

        $name = $r['nom_complet'] ?? ($r['nom_raison_sociale'] ?? '');
        if (empty($name)) return null;

        $siren = $r['siren'] ?? '';
        $siret = $siege['siret'] ?? '';
        $ape = $siege['activite_principale'] ?? ($r['activite_principale'] ?? '');

        // Build address
        $addressParts = array_filter(array(
            $siege['numero_voie'] ?? '',
            $siege['indice_repetition'] ?? '',
            $siege['type_voie'] ?? '',
            $siege['libelle_voie'] ?? '',
        ));
        $address = trim(implode(' ', $addressParts));
        if (empty($address) && !empty($siege['adresse'])) {
            // Some records have a full "adresse" field - strip postal code + city
            $address = $siege['adresse'];
        }

        $postalCode = $siege['code_postal'] ?? '';
        $city = $siege['libelle_commune'] ?? '';

        return array(
            'name' => $name,
            'siren' => $siren,
            'siret' => $siret,
            'tva_intra' => $this->computeTvaIntra($siren),
            'ape' => $ape,
            'legal_form' => $r['nature_juridique'] ?? '',
            'address' => $address,
            'address2' => $siege['complement_adresse'] ?? '',
            'postal_code' => $postalCode,
            'city' => $city,
            'country_code' => 'FR',
            'is_head_office' => !empty($siege['est_siege']),
            'active' => ($siege['etat_administratif'] ?? 'A') === 'A',
            'employees' => $r['tranche_effectif_salarie'] ?? '',
            'creation_date' => $r['date_creation'] ?? '',
        );
    }

    /**
     * Compute FR intra-community VAT number from SIREN.
     * Formula: FR + (12 + 3 * (SIREN % 97)) % 97 + SIREN
     */
    public function computeTvaIntra($siren)
    {
        $siren = preg_replace('/[^0-9]/', '', (string) $siren);
        if (strlen($siren) !== 9) return '';
        $key = (12 + 3 * ((int) $siren % 97)) % 97;
        return sprintf('FR%02d%s', $key, $siren);
    }

    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'Dolibarr-Dolisirene/1.0',
            CURLOPT_HTTPHEADER => array('Accept: application/json'),
            CURLOPT_FOLLOWLOCATION => true,
        ));
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false || $code >= 400) {
            $this->error = !empty($err) ? $err : ('HTTP '.$code);
            return false;
        }
        return $body;
    }
}
