<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-test-data',
    description: 'Add test data for houses and users',
)]
class AddTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $houses = [
                        [
                            'name' => 'Дом у моря',
                            'beds' => 2,
                            'amenities' => 'санузел,душевая кабина',
                            'distanceToSea' => 1,
                            'pricePerNight' => 5000
                        ],
                        [
                            'name' => 'Большой дом',
                            'beds' => 4,
                            'amenities' => 'санузел,кухня',
                            'distanceToSea' => 3,
                            'pricePerNight' => 3000
                        ],
                        [
                            'name' => 'Люкс',
                            'beds' => 3,
                            'amenities' => 'санузел,душевая кабина,кондиционер',
                            'distanceToSea' => 2,
                            'pricePerNight' => 7000
                        ],
                    ];

        foreach ($houses as $houseData) {
            $house = new House();
            $house->setName($houseData['name']);
            $house->setBeds($houseData['beds']);
            $house->setAmenities($houseData['amenities']);
            $house->setDistanceToSea($houseData['distanceToSea']);
            $house->setPricePerNight($houseData['pricePerNight']);
            $house->setIsAvailable(true);

            $this->entityManager->persist($house);
        }

        $this->entityManager->flush();

        $io->success('Тестовые данные добавлены!');
        return Command::SUCCESS;
    }
}
