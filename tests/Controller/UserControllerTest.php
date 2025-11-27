<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM booking');
        $connection->executeStatement('DELETE FROM users');
        $connection->executeStatement('DELETE FROM house');
    }

    public function testCreateUser(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+79161234567',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertEquals('Test User', $response['data']['name']);
        $this->assertEquals('test@example.com', $response['data']['email']);
        $this->assertEquals('+79161234567', $response['data']['phone']);
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $user = new User();
        $user->setName('Existing User');
        $user->setEmail('duplicate@example.com');
        $user->setPhone('+79161111111');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Создаем пользователя с таким же email
        $data = [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'phone' => '+79162222222',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('уже существует', $response['message']);
    }

    public function testCreateUserWithDuplicatePhone(): void
    {
        $user = new User();
        $user->setName('Existing User');
        $user->setEmail('test1@example.com');
        $user->setPhone('+79161111111');
        $user->setPassword('password123');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Создаем пользователя с таким же телефоном
        $data = [
            'name' => 'Another User',
            'email' => 'test2@example.com',
            'phone' => '+79161111111',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('телефоном', $response['message']);
    }

    public function testCreateUserWithMissingFields(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+79161234567'
            // password отсутствует
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('обязательны', $response['message']);
    }

    public function testCreateUserWithInvalidEmail(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'phone' => '+79161234567',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }

    public function testCreateUserWithEmptyData(): void
    {
        $data = [];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
    }
}
