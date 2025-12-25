<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Override;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Override]
    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();
        $googleId = $response->getUsername();
        $email = $response->getEmail();
        $fullName = $response->getRealName();
        $avatar = $response->getProfilePicture();

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);

        if (!$user && $email) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        }

        if (!$user) {
            $user = new User();
            $user->setEmail($email);

            if ($fullName) {
                $user->setName($fullName);
            } else {
                $user->setName(explode('@', $email)[0]);
            }

            $user->setPhone('+7' . rand(1000000000, 9999999999));

            $user->setPassword(bin2hex(random_bytes(16)));

            $user->setRoles(['ROLE_USER']);

            $user->setCreatedAt(new DateTimeImmutable());
        }

        $user->setGoogleId($googleId);
        if ($avatar) {
            $user->setAvatar($avatar);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('Refresh not supported');
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
