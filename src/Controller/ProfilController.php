<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\Password;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('/profile', name: 'profil_')]
class ProfilController extends AbstractController
{
    
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('profil/index.html.twig', [
            'controller_name' => 'ProfilController',
        ]);
    }

    #[Security("is_granted('ROLE_USER') and user === this.getUser()")]
    #[Route('/{id}/moncompte', name: 'show', requirements: ['id' => '\d+'], methods: ["GET"])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Security("is_granted('ROLE_USER') and user === this.getUser()")]
    #[Route('/{id}/edite-compte', name: 'edit',  methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        if( $request->attributes->get('_route') == 'profil_edit' && $this->getUser()->getId() != $user->getId()){
            $this->denyAccessUnlessGranted('ROLE_ADMIN', null);
        }
        
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Vos données ont bien été mise à jour.');
        }

        return $this->render('profil/index.html.twig', [
            'controller_name' => 'ProfilController',
        ]);
    }

    /**
     * @param Request $request
     * @param User $user
     * @param UserRepository $repository
     * @param UserPasswordHasherInterface $encoder
     * 
     * @return Response
     */
    #[Route('/{id}/newpass', name: 'edit_pass', methods: ['GET', 'POST'])]
    public function editPassword(Request $request, User $user,UserRepository $repository, UserPasswordHasherInterface $encoder): Response
    {
        $pass = new Password();
        $form = $this->createForm(ChangePassType::class, $pass);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $checkPass = $encoder->isPasswordValid($user, $pass->getOldPass());

            if ($checkPass === true) {
                $encodedPassword = $encoder->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $repository->upgradePassword($user, $encodedPassword);
                $this->addFlash('success', "Votre mot de passe a bien été mis à jour.");

            } else {
                $this->addFlash('danger', "Erreur lors de la mise à jour du mot de passe.");
            }

            return $this->redirectToRoute('edit_pass', ['id' => $user->getId(),]);
        }

        return $this->render('user/pass.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        if ( $this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            
            $userRepository->remove($user, true);
            
            $this->addFlash('success', "L'utilisateur a bien été supprimé.");
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }

}
