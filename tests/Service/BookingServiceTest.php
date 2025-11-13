<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase
{
    private $entityManager;
    private $bookingService;

    #[Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookingService = new BookingService($this->entityManager);
    }

    public function testCreateBooking(): void
    {
        $user = new User();
        $house = new House();
        $comment = 'Test comment';

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Booking::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $booking = $this->bookingService->createBooking($user, $house, $comment);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($user, $booking->getCustomer());
        $this->assertEquals($house, $booking->getHouse());
        $this->assertEquals($comment, $booking->getComment());
        $this->assertEquals('active', $booking->getStatus());
        $this->assertNotNull($booking->getCreatedAt());
    }
}
