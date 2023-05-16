<?php

namespace App\Controller;

use App\Service\CallApiService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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
        $response = $apiCovid->getAllDataFranceByDate($date) ;
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


    
}
