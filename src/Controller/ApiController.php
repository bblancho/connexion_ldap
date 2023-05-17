<?php

namespace App\Controller;

use DateTime;
use DateInterval;
use App\Service\CallApiService;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiController extends AbstractController
{

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    #[Route('/api', name: 'app_api')]
    public function index(): Response
    {
        $url     = 'https://api.github.com/users/bblancho/repos';
        $method  = 'GET';
        $options =
        [
            'query' => [
                'sort' => 'name', // champs json ('id','description'...)
                'direction'=> 'asc'
            ],
        ];

        $response = $this->httpClient->request(
            'GET',
            $url ,
            $options
        );

        // Get Content
        $content = $response->getContent();
        $reposGit = $response->toArray();

        $statusCode = $response->getStatusCode();
        // $statusCode = 200

        $contentType = $response->getHeaders()['content-type'][0];
        // $contentType = 'application/json'

        return $this->render('api/index.html.twig', compact(
            'reposGit',
        ));
    }

    #[Route('/api/show/{id}', name: 'app_api_show')]
    public function show($id): Response
    {
        $url     = "https://api.github.com/repositories/{$id} "; 
        $method  = 'GET';

        $response = $this->httpClient->request(
            'GET',
            $url ,
        );

        $statusCode = $response->getStatusCode() ;

        if( $statusCode == Response::HTTP_NOT_FOUND )
        {
            throw new NotFoundHttpException("No response") ;
        }

        // Get Content
        $reposGit = $response->toArray();

        return $this->render('api/show.html.twig', compact(
            'reposGit',
        ));
    }

    #[Route('/api/covid/france/{date}', name: 'app_api_data_all_departement')]
    public function showDataDepartements($date, CallApiService $apiCovid): Response
    {
        $response = $apiCovid->getAllDataByDate($date) ;
        $responseDepartements = $apiCovid->getDataDepartmentsByDate($date) ;

        // dd($response) ;

        $statusCode = $response->getStatusCode() ;

        if( $statusCode == Response::HTTP_NOT_FOUND )
        {
            throw new NotFoundHttpException("No response") ;
        }

        // Get Content
        $dataFrance = $response->toArray();
        $Alldepartments = $responseDepartements->toArray();
        // dd($dataFrance) ;

        return $this->render('api/covid.html.twig', compact(
            'dataFrance','Alldepartments'
        ));
    }

    /**
     * @Route("/api/covid/department/{department}/date/{date}", name="api_department_date")
     */
    public function departement_by_date($department, $date, CallApiService $apiCovid): Response
    {
        $response = $apiCovid->getDataByDepartmentByDate($department, $date) ;

        $department = $response->toArray();

        return $this->render('api/departementByDate.html.twig', [
            'department' =>  $department ,
        ]);
    }

    /**
     * @Route("/api/covid/department/{department}", name="api_department")
     */
    public function indexApi($department, CallApiService $apiCovid): Response
    {
        $response = $apiCovid->getDataDepartment($department) ;
        
        // Get Content
        $department = $response->toArray();

        // $label = [];
        $hospitalisation = 0;
        $rea = 0;
        $date = $department[0]['date'];
        $nom_dep = $department[0]['lib_dep'];

        for ( $i=0; $i < count($department); $i++) { 
            $hospitalisation += $department[$i]['hosp'] ;
            $rea += $department[$i]['rea'] ;
        }

        // for ($i=1; $i < 8; $i++) { 
        //     $date = New DateTime('- '. $i .' day');
        //     $datas = $callApiService->getAllDataByDate($date->format('Y-m-d'));

        //     foreach ($datas['allFranceDataByDate'] as $data) {
        //         if( $data['nom'] === $department) {
        //             $label[] = $data['date'];
        //             $hospitalisation[] = $data['nouvellesHospitalisations'];
        //             $rea[] = $data['nouvellesReanimations'];
        //             break;
        //         }
        //     }
        // }

        // $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        // $chart->setData([
        //     'labels' => array_reverse($label),
        //     'datasets' => [
        //         [
        //             'label' => 'Nouvelles Hospitalisations',
        //             'borderColor' => 'rgb(255, 99, 132)',
        //             'data' => array_reverse($hospitalisation),
        //         ],
        //         [
        //             'label' => 'Nouvelles entrées en Réa',
        //             'borderColor' => 'rgb(46, 41, 78)',
        //             'data' => array_reverse($rea),
        //         ],
        //     ],
        // ]);

        // $chart->setOptions([/* ... */]);

        
            
        return $this->render('api/departement.html.twig', [
            'department' =>  $department ,
            'nom_dep' => $nom_dep ,
            'hospitalisation' => $hospitalisation ,
            'rea' => $rea ,
            'date' => $date
        ]);
    }

    /**
     * @Route("/api/department/{department}/date/{date}", name="api_department_2")
     */
    public function apiDep($department, $date, CallApiService $apiCovid, ChartBuilderInterface $chartBuilder): Response
    {
        $label_date = [];
        $hospitalisation = []; // courbe hospi
        $rea = []; // courbe Rea
        $data_departement = [] ;

        $interval = new DateInterval('P1D');
        $date = new DateTime($date) ;
        $current_date = $date->format('d-m-Y') ;
        
        // Les datas sur les 7 derniers jours
        for ($i=1; $i < 8; $i++) { 
            // echo $date->format('d-m-Y') ."<br/>";
            
            $response = $apiCovid->getDataDepartmentsByDate( $date->format('d-m-Y') );
            // Get Content
            $datas = $response->toArray();
            // dd($datas) ;

            foreach ($datas as $data) {
                if( $data['lib_dep'] === $department) {
                    $data_departement['nom'] = $data['lib_dep'];
                    $data_departement['hosp'] = $data['hosp'];
                    $data_departement['rea']  = $data['rea'];
                    $label_date[] = $data['date'];
                    $hospitalisation[] = $data['hosp'];
                    $rea[] = $data['rea'];
                    break; // pour ne pas boucler sur toute les données
                }
            }

            // dd($data_departement) ;

            // Création d'un graphique
            $chart = $chartBuilder->createChart(Chart::TYPE_LINE); // tableau de tyle line
            $chart->setData([
                'labels' => array_reverse($label_date),
                'datasets' => [
                    [
                        'label' => 'Nouvelles Hospitalisations',
                        'borderColor' => 'rgb(255, 99, 132)',
                        'data' => array_reverse($hospitalisation),
                    ],
                    [
                        'label' => 'Nouvelles entrées en Réa',
                        'borderColor' => 'rgb(46, 41, 78)',
                        'data' => array_reverse($rea),
                    ],
                ],
            ]);

            $chart->setOptions([/* ... */]);
            
            $date->sub($interval) ; // on soustrait la date
        }// fin for   
            
        return $this->render('api/departement.html.twig', [
            'department' =>  $data_departement ,
            'graphique'  => $chart ,
            'date' => $current_date ,
        ]);
    }


    
}
