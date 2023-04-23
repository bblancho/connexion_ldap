<?php

namespace App\Security;

use App\Entity\User; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * Cette fonction indique si l'authentificateur prend en charge la requête donnée
     * Elle doit renvoyer une valeur booléenne
     */
    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_google_check' && $request->isMethod('GET') ;
    }

    public function getGoogleClient()
    {
        return $this->clientRegistry->getClient('google') ;
    }

    /**
     * Cette fonction obtient les identifiants d'authentification de la requête 
     * et les renvoie dans un tableau ou une variable, 
     * cela dépend de votre système
     */
    public function authenticate(Request $request): Passport
    {
        $client      = $this->getGoogleClient();
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
               // dd($googleUser);
                
                $email = $googleUser->getEmail();
                
                // 1) have they logged in with googleUser before? Easy!
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleUser->getId()]);
                
                // 2) do we have a matching user by email?
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) ;

                //User doesnt exist, we create it !
                if ( !$existingUser && !$user ) {
                    
                    /** @var User $existingUser */
                    $existingUser = new User();

                    $existingUser->setEmail( $googleUser->getEmail()) 
                        ->setNom( $googleUser->getLastName()) 
                        ->setPrenom( $googleUser->getFirstName() )
                        ->setPhone("0147859685")
                        ->setGoogleId( $googleUser->getId() )
                        // ->setPassword('')
                        ->setHostDomain( $googleUser->getHostedDomain() )
                        ->setRoles(array('ROLE_USER'))
                    ;

                    // dd($existingUser);
                    
                    $this->entityManager->persist($existingUser);
                    $this->entityManager->flush();

                }

                $existingUser
                    ->setGoogleId( $googleUser->getId() )
                    ->setHostDomain($googleUser->getHostedDomain())
                ;

                // dd($existingUser);
                
                return $existingUser;
            })
        );
    }

    /**
     * Cette fonction est appelée lorsque l'authentification a été exécutée avec succès.
     * Il doit renvoyer un objet Response ou null.
     *
     * @param Request $request
     * @param \Symfony\Component\Security\Core\Authentication\Token\
     *  $token
     * @param string $providerKey The provider (i.e. firewall) key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetUrl = $this->router->generate('app_home');

        return new RedirectResponse($targetUrl);
    }

    /**
     * Cette fonction est appelée lorsque l'authentification a été exécutée, 
     * mais a échoué (par exemple mauvaise apikey).
     * Il doit renvoyer un objet Response ou null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
    
    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/connexion', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

}