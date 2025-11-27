<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createBooking(User $user, House $house, string $comment): Booking
    {
        $booking = new Booking();
        $booking->setCustomer($user);
        $booking->setHouse($house);
        $booking->setComment($comment);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking;
    }
}
