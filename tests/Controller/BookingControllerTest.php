<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\House;
use App\Entity\User;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $jwtService;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->jwtService = $container->get(JwtService::class);

        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM booking');
        $connection->executeStatement('DELETE FROM users');
        $connection->executeStatement('DELETE FROM house');
    }

    private function createTestHouse(): House
    {
        $house = new House();
        $house->setName('Test House');
        $house->setBeds(2);
        $house->setAmenities('test');
        $house->setDistanceToSea(1);
        $house->setPricePerNight(1000);
        $house->setIsAvailable(true);

        $this->entityManager->persist($house);
        $this->entityManager->flush();

        return $house;
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setPhone('+79161234567');
        $user->setPassword('password123');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function testCreateBooking(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();

        $token = $this->jwtService->generateToken($user);

        $data = [
            'user_id' => $user->getId(),
            'house_id' => $house->getId(),
            'comment' => 'Test booking comment'
        ];

        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertEquals('Test booking comment', $response['data']['comment']);
    }

    public function testCreateBookingWithNonExistentUser(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();

        $token = $this->jwtService->generateToken($user);

        $data = [
            'user_id' => 999,
            'house_id' => $house->getId(),
            'comment' => 'Test booking'
        ];

        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Пользователь не найден', $response['message']);
    }

    public function testCreateBookingWithNonExistentHouse(): void
    {
        $user = $this->createTestUser();

        $token = $this->jwtService->generateToken($user);

        $data = [
            'user_id' => $user->getId(),
            'house_id' => 999,
            'comment' => 'Test booking'
        ];

        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Домик не найден', $response['message']);
    }

    public function testCreateBookingWithoutToken(): void
    {
        $user = $this->createTestUser();
        $house = $this->createTestHouse();

        $data = [
            'user_id' => $user->getId(),
            'house_id' => $house->getId(),
            'comment' => 'Test booking comment'
        ];

        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
