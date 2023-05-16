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

    /**
     * @Route("/department/{department}", name="app_department")
     */
    public function indexApi(string $department, CallApiService $callApiService, ChartBuilderInterface $chartBuilder): Response
    {
        $label = [];
        $hospitalisation = [];
        $rea = [];

        for ($i=1; $i < 8; $i++) { 
            $date = New DateTime('- '. $i .' day');
            $datas = $callApiService->getAllDataByDate($date->format('Y-m-d'));

            foreach ($datas['allFranceDataByDate'] as $data) {
                if( $data['nom'] === $department) {
                    $label[] = $data['date'];
                    $hospitalisation[] = $data['nouvellesHospitalisations'];
                    $rea[] = $data['nouvellesReanimations'];
                    break;
                }
            }
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => array_reverse($label),
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

        return $this->render('department/index.html.twig', [
            'data' => $callApiService->getDepartmentData($department),
            'chart' => $chart,
        ]);
    }


    
}
