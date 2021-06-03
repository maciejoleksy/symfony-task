<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('users', name: 'user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $users = $userRepository->findAll();

        $result = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('user/index.html.twig', [
            'users' => $result,
        ]);
    }

    #[Route('register', name: 'user_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
            $user->setDisable(false);
            $user->setRoles(['ROLE_USER']);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('security/register.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
    #[Route('users/{id}/activate', name: 'user_activate', methods: ['GET', 'HEAD'])]
    public function activate(User $user): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user->setDisable(false));
        $entityManager->flush();

        return $this->redirectToRoute('user_index');
    }

    #[Route('users/{id}/deactivate', name: 'user_deactivate', methods: ['GET', 'HEAD'])]
    public function deactivate(User $user): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user->setDisable(true));
        $entityManager->flush();

        return $this->redirectToRoute('user_index');
    }
}
