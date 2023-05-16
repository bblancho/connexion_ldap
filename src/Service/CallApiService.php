<?php

namespace App\Service;

use DateTime;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CallApiService
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function getAllDataFranceByDate($date)
    {
        // DD/MM/YYYY
        return $this->getApi('france-by-date/'. $date);
    }

    public function getDataDepartmentsByDate($date)
    {
        // DD/MM/YYYY
        return $this->getApi('departements-by-date/' . $date);
    }

    private function getApi(string $var)
    {
        $response = $this->client->request(
            'GET',
            'https://coronavirusapifr.herokuapp.com/data/' . $var
        );

        // return $response->toArray();
        return $response ;
    }

    // Données détaillées par DÉPARTEMENTS pour une date précise
        // "https://coronavirusapifr.herokuapp.com/data/departements-by-date/11-10-2021"
    
    // Données globales pour la FRANCE pour une date précise
        // "https://coronavirusapifr.herokuapp.com/data/france-by-date/11-10-2021"

    // Données disponibles pour un DÉPARTEMENT précis
        // "https://coronavirusapifr.herokuapp.com/data/departement/rhone"
    /*
        tx_pos: 9.74,
        tx_incid: 183.4,
        lib_dep: "Rhône",
        TO: 0.293198892843021,
        R: 1.37509386052026,
        rea: 1483,
        hosp: 8231,
        rad: 101460,
        dchosp: 21874,
        incid_rea: 73,
        incid_hosp: 503,
        incid_rad: 184,
        incid_dchosp: 46,
        conf: null,
        conf_j1: null,
        pos: 3235,
        esms_dc: null,
        dc_tot: null,
        pos_7j: 123089,
        cv_dose1: null,
        esms_cas: null
     */
}
