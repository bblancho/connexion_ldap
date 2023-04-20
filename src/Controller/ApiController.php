<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

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

    
}
