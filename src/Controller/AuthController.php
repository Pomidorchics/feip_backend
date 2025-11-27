<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private JwtService $jwtService,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phone']) || !isset($data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Поля phone и password обязательны'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['phone' => $data['phone']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Неверный телефон или пароль'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtService->generateToken($user);

        return $this->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'roles' => $user->getRoles()
                ]
            ]
        ]);
    }

    #[Route('/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'Успешный выход из системы'
        ]);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
