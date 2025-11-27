<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private JwtService $jwtService
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization') && 
               str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');
        
        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $token = substr($authorizationHeader, 7);
        
        if (!$this->jwtService->validateToken($token)) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        $payload = $this->jwtService->getPayload($token);
        
        if (!$payload) {
            throw new CustomUserMessageAuthenticationException('Invalid token payload');
        }

        $userIdentifier = $payload['phone'] ?? null;
        
        if (!$userIdentifier) {
            throw new CustomUserMessageAuthenticationException('User identifier not found in token');
        }

        return new SelfValidatingPassport(
            new UserBadge($userIdentifier, function ($userIdentifier) {
                return $this->userRepository->findOneBy(['phone' => $userIdentifier]);
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'success' => false,
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ], Response::HTTP_UNAUTHORIZED);
    }
}