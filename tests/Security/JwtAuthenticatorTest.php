<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\JwtAuthenticator;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class JwtAuthenticatorTest extends KernelTestCase
{
    private JwtAuthenticator $authenticator;

    /** @var UserRepository&MockObject */
    private $userRepositoryMock;

    /** @var JwtService&MockObject */
    private $jwtServiceMock;

    /** @var EntityManagerInterface&MockObject */
    private $entityManagerMock;

    #[Override]
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->jwtServiceMock = $this->createMock(JwtService::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $this->authenticator = new JwtAuthenticator(
            $this->userRepositoryMock,
            $this->entityManagerMock,
            $this->jwtServiceMock
        );
    }

    public function testSupportsWithBearerToken(): void
    {
        $request = Request::create('/api/me');
        $request->headers->set('Authorization', 'Bearer valid_token_here');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsWithoutAuthorizationHeader(): void
    {
        $request = Request::create('/api/me');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidToken(): void
    {
        $request = Request::create('/api/me');
        $request->headers->set('Authorization', 'Bearer valid_token_here');

        $user = new User();
        $user->setPhone('+79991234567');

        $payload = [
            'user_id' => 1,
            'phone' => '+79991234567',
            'roles' => ['ROLE_USER']
        ];

        $this->jwtServiceMock->method('validateToken')
            ->with('valid_token_here')
            ->willReturn(true);

        $this->jwtServiceMock->method('getPayload')
            ->with('valid_token_here')
            ->willReturn($payload);

        $this->userRepositoryMock->method('findOneBy')
            ->with(['phone' => '+79991234567'])
            ->willReturn($user);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token');

        $request = Request::create('/api/me');
        $request->headers->set('Authorization', 'Bearer invalid_token');

        $this->jwtServiceMock->method('validateToken')
            ->with('invalid_token')
            ->willReturn(false);

        $this->authenticator->authenticate($request);
    }
}
