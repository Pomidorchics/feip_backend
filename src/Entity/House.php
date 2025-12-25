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
use App\Controller\HousesController;
use App\Repository\HouseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            name: 'get_house',
            uriTemplate: '/api/houses/{id}',
            controller: HousesController::class . '::getHouse'
        ),
        new GetCollection(
            name: 'get_houses',
            uriTemplate: '/api/houses',
            controller: HousesController::class . '::getHouses'
        ),
        new Post(
            name: 'create_house',
            uriTemplate: '/api/houses',
            controller: HousesController::class . '::createHouse'
        ),
        new Put(
            name: 'update_house',
            uriTemplate: '/api/houses/{id}',
            controller: HousesController::class . '::updateHouse'
        ),
        new Patch(
            name: 'partial_update_house',
            uriTemplate: '/api/houses/{id}',
            controller: HousesController::class . '::partialUpdateHouse'
        ),
        new Delete(
            name: 'delete_house',
            uriTemplate: '/api/houses/{id}',
            controller: HousesController::class . '::deleteHouse'
        )
    ],
)]

class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['house:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['house:read', 'booking:read'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['house:read'])]
    private ?int $beds = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['house:read'])]
    private ?string $amenities = null;

    #[ORM\Column]
    #[Groups(['house:read'])]
    private ?int $distanceToSea = null;

    #[ORM\Column]
    #[Groups(['house:read'])]
    private ?int $pricePerNight = null;

    #[ORM\Column]
    #[Groups(['house:read'])]
    private ?bool $isAvailable = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'house')]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->isAvailable = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBeds(): ?int
    {
        return $this->beds;
    }

    public function setBeds(int $beds): static
    {
        $this->beds = $beds;

        return $this;
    }

    public function getAmenities(): ?string
    {
        return $this->amenities;
    }

    public function setAmenities(?string $amenities): static
    {
        $this->amenities = $amenities;

        return $this;
    }

    public function getDistanceToSea(): ?int
    {
        return $this->distanceToSea;
    }

    public function setDistanceToSea(int $distanceToSea): static
    {
        $this->distanceToSea = $distanceToSea;

        return $this;
    }

    public function getPricePerNight(): ?int
    {
        return $this->pricePerNight;
    }

    public function setPricePerNight(int $pricePerNight): static
    {
        $this->pricePerNight = $pricePerNight;

        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setHouse($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getHouse() === $this) {
                $booking->setHouse(null);
            }
        }

        return $this;
    }
}
