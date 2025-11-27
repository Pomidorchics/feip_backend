<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Override;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    private JwtService $jwtService;
    private $entityManager;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->jwtService = static::getContainer()->get(JwtService::class);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM users');
    }

    private function createTestUser(
        string $phone = '+79991234567',
        string $password = 'password123',
        string $name = 'Test User',
        string $email = 'test@example.com',
        array $roles = ['ROLE_USER']
    ): User {
        $user = new User();
        $user->setPhone($phone);
        $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $user->setName($name);
        $user->setEmail($email);
        $user->setRoles($roles);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testLoginSuccess(): void
    {
        // Arrange
        $user = $this->createTestUser();

        $loginData = [
            'phone' => '+79991234567',
            'password' => 'password123'
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('token', $responseData['data']);
        $this->assertArrayHasKey('user', $responseData['data']);
        $this->assertEquals($user->getId(), $responseData['data']['user']['id']);
        $this->assertEquals($user->getPhone(), $responseData['data']['user']['phone']);
        $this->assertEquals($user->getName(), $responseData['data']['user']['name']);

        $token = $responseData['data']['token'];
        $this->assertTrue($this->jwtService->validateToken($token));
    }

    public function testLoginWithInvalidPhone(): void
    {
        // Arrange
        $this->createTestUser();

        $loginData = [
            'phone' => '+79998887766',
            'password' => 'password123'
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $response = $this->client->getResponse();
        echo 'Status Code: ' . $response->getStatusCode() . "\n";
        echo 'Response Content: ' . $response->getContent() . "\n";

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseData = json_decode($response->getContent(), true);

        $this->assertNotNull($responseData, 'Response should be valid JSON');
        $this->assertIsArray($responseData, 'Response should be an array');
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        // Arrange
        $this->createTestUser();

        $loginData = [
            'phone' => '+79991234567',
            'password' => 'wrongpassword'
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Неверный телефон или пароль', $responseData['message']);
    }

    public function testLoginWithoutRequiredFields(): void
    {
        // Test without phone
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'password123'])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Test without password
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['phone' => '+79991234567'])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Test with empty data
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMeWithValidToken(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $token = $this->jwtService->generateToken($user);

        // Act
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['success']);
        $this->assertEquals($user->getId(), $responseData['data']['id']);
        $this->assertEquals($user->getPhone(), $responseData['data']['phone']);
        $this->assertEquals($user->getName(), $responseData['data']['name']);
        $this->assertEquals($user->getEmail(), $responseData['data']['email']);
        $this->assertEquals($user->getRoles(), $responseData['data']['roles']);
    }

    public function testGetMeWithoutToken(): void
    {
        // Act
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $responseContent = $this->client->getResponse()->getContent();

        if (empty($responseContent)) {
            echo "DEBUG testGetMeWithoutToken: Empty response\n";
            return;
        }

        $responseData = json_decode($responseContent, true);

        if ($responseData === null) {
            echo 'DEBUG testGetMeWithoutToken: Not JSON, got: ' . $responseContent . "\n";
            return;
        }

        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
    }

    public function testGetMeWithInvalidToken(): void
    {
        // Act
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer invalid_token_here'
            ]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertFalse($responseData['success']);
    }

    public function testGetMeWithExpiredToken(): void
    {
        // Arrange
        $user = $this->createTestUser();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode([
            'user_id' => $user->getId(),
            'phone' => $user->getPhone(),
            'roles' => $user->getRoles(),
            'exp' => time() - 3600 // expired 1 hour ago
        ]));

        $jwtService = static::getContainer()->get(JwtService::class);
        $secretKey = $this->getPrivateProperty($jwtService, 'secretKey');

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $secretKey, true)
        );

        $expiredToken = "$header.$payload.$signature";

        // Act
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $expiredToken
            ]
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogout(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $token = $this->jwtService->generateToken($user);

        // Act
        $this->client->request(
            'POST',
            '/api/logout',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['success']);
        $this->assertEquals('Успешный выход из системы', $responseData['message']);
    }

    public function testJwtTokenContainsCorrectPayload(): void
    {
        // Arrange
        $user = $this->createTestUser();

        // Act
        $token = $this->jwtService->generateToken($user);
        $payload = $this->jwtService->getPayload($token);

        // Assert
        $this->assertIsArray($payload);
        $this->assertEquals($user->getId(), $payload['user_id']);
        $this->assertEquals($user->getPhone(), $payload['phone']);
        $this->assertEquals($user->getRoles(), $payload['roles']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertGreaterThan(time(), $payload['exp']); // expiration in future
    }

    public function testMultipleLoginsGenerateDifferentTokens(): void
    {
        // Arrange
        $user = $this->createTestUser();

        // Act
        $token1 = $this->jwtService->generateToken($user);
        sleep(1);
        $token2 = $this->jwtService->generateToken($user);

        // Assert
        $this->assertNotEquals($token1, $token2);
        $this->assertTrue($this->jwtService->validateToken($token1));
        $this->assertTrue($this->jwtService->validateToken($token2));
    }

    public function testUserWithAdminRole(): void
    {
        // Arrange
        $adminUser = $this->createTestUser(
            '+79997776655',
            'adminpass',
            'Admin User',
            'admin@example.com',
            ['ROLE_USER', 'ROLE_ADMIN']
        );

        $token = $this->jwtService->generateToken($adminUser);

        // Act
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($responseData['success']);
        $this->assertContains('ROLE_ADMIN', $responseData['data']['roles']);
        $this->assertContains('ROLE_USER', $responseData['data']['roles']);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
