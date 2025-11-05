<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
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
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    public function testCreateBooking(): void
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

        $this->assertResponseStatusCodeSame(201);
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertEquals('Test booking comment', $response['data']['comment']);
    }

    public function testCreateBookingWithNonExistentUser(): void
    {
        $house = $this->createTestHouse();

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
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(404);
    }

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }
}