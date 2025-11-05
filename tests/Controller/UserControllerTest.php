<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeQuery('DELETE FROM booking');
        $connection->executeQuery('DELETE FROM users');
        $connection->executeQuery('DELETE FROM house');
        
        $connection->executeQuery('ALTER SEQUENCE booking_id_seq RESTART WITH 1');
        $connection->executeQuery('ALTER SEQUENCE users_id_seq RESTART WITH 1');
        $connection->executeQuery('ALTER SEQUENCE house_id_seq RESTART WITH 1');
    }

    public function testCreateUser(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+79161234567'
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
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $user = new User();
        $user->setName('Existing User');
        $user->setEmail('duplicate@example.com');
        $user->setPhone('+79161111111');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Создаем пользователя с таким же email
        $data = [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'phone' => '+79162222222'
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
    }

    public function testCreateUserWithMissingFields(): void
    {
        $data = [
            'name' => 'Test User'
            // email и phone отсутствуют
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
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}