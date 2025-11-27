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
            return new JsonResponse([
                'success' => false,
                'message' => 'Поля user_id и house_id обязательны'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($data['user_id']);
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Пользователь не найден'
            ], Response::HTTP_NOT_FOUND);
        }

        $house = $this->houseRepository->find($data['house_id']);
        if (!$house) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Домик не найден'
            ], Response::HTTP_NOT_FOUND);
        }

        if (!$house->isAvailable()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Домик недоступен для бронирования'
            ], Response::HTTP_BAD_REQUEST);
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
