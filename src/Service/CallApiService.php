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

    public function getFranceData(): array
    {
        return $this->getApi('FranceLiveGlobalData');
    }

    public function getAllData(): array
    {
        return $this->getApi('AllLiveData');
    }

    public function getAllDataByDate($date): array
    {
        return $this->getApi('AllDataByDate?date=' . $date);
    }

    public function getDepartmentData($department): array
    {
        return $this->getApi('LiveDataByDepartement?Departement=' . $department);
    }

    private function getApi(string $var)
    {
        $response = $this->client->request(
            'GET',
            'https://coronavirusapifr.herokuapp.com/data/' . $var
        );

        return $response->toArray();
    }

    // "https://coronavirusapifr.herokuapp.com/data/france-by-date/11-10-2021"

    /*
        tx_pos: 9.74,
        tx_incid: 183.4,
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
