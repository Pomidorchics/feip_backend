<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\BookingController;
use App\Repository\BookingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            name: 'get_booking',
            uriTemplate: '/api/bookings/{id}',
            controller: BookingController::class . '::getBooking'
        ),
        new GetCollection(
            name: 'get_bookings',
            uriTemplate: '/api/bookings',
            controller: BookingController::class . '::getBookings'
        ),
        new Post(
            name: 'create_booking',
            uriTemplate: '/api/bookings',
            controller: BookingController::class . '::createBooking'
        ),
        new Put(
            name: 'update_booking',
            uriTemplate: '/api/bookings/{id}',
            controller: BookingController::class . '::updateBooking'
        ),
        new Patch(
            name: 'partial_update_booking',
            uriTemplate: '/api/bookings/{id}',
            controller: BookingController::class . '::partialUpdateBooking'
        ),
        new Delete(
            name: 'delete_booking',
            uriTemplate: '/api/bookings/{id}',
            controller: BookingController::class . '::deleteBooking'
        )
    ],
)]

class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 1000, nullable: true)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['booking:read'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 20)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?User $customer = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?House $house = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->status = 'active';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(?House $house): static
    {
        $this->house = $house;

        return $this;
    }
}
