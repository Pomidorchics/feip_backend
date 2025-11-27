<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\HouseRepository;
use App\Repository\UserRepository;
use App\Service\BookingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/bookings')]
class BookingController extends AbstractController
{
    public function __construct(
        private BookingService $bookingService,
        private UserRepository $userRepository,
        private HouseRepository $houseRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'booking_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id']) || !isset($data['house_id'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Поля user_id и house_id обязательны');
        }

        $user = $this->userRepository->find($data['user_id']);
        if (!$user) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Пользователь не найден');
        }

        $house = $this->houseRepository->find($data['house_id']);
        if (!$house) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Домик не найден');
        }

        if (!$house->isAvailable()) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Домик недоступен для бронирования');
        }

        $comment = $data['comment'] ?? '';

        $booking = $this->bookingService->createBooking($user, $house, $comment);

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $booking->getId(),
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName()
                ],
                'house' => [
                    'id' => $house->getId(),
                    'name' => $house->getName()
                ],
                'comment' => $booking->getComment(),
                'status' => $booking->getStatus(),
                'created_at' => $booking->getCreatedAt()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_CREATED);
    }
}
