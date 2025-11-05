<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;

class BookingService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

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