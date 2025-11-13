<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CSVService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class HousesController extends AbstractController
{
    private CSVService $csvService;

    public function __construct(CSVService $csvService)
    {
        $this->csvService = $csvService;
    }

    /**
     * Метод получения списка свободных домиков
     * GET /api/houses/available
     */
    #[Route('/api/houses/available', name: 'available_houses', methods: ['GET'])]
    public function getAvailableHouses(): JsonResponse
    {
        $houses = $this->csvService->readHouses();
        $availableHouses = array_filter($houses, function ($house) {
            return isset($house['is_available']) && $house['is_available'] == '1';
        });

        return $this->json([
            'success' => true,
            'data' => array_values($availableHouses),
            'count' => count($availableHouses)
        ]);
    }

    /**
     * Метод создания заявки на бронирование домика
     * POST /api/bookings
     */
    #[Route('/api/bookings', name: 'create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['phone']) || empty($data['phone'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Поле "phone" обязательно для заполнения');
        }

        if (!isset($data['house_id']) || empty($data['house_id'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Поле "house_id" обязательно для заполнения');
        }

        $phone = $data['phone'];
        $houseId = (int)$data['house_id'];
        $comment = $data['comment'] ?? '';

        if (!preg_match('/^\+?[0-9\s\-\(\)]{10,}$/', $phone)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Неверный формат номера телефона');
        }

        $houses = $this->csvService->readHouses();
        $houseExists = false;
        $houseDetails = null;

        foreach ($houses as $house) {
            if (isset($house['id']) && $house['id'] == $houseId) {
                if ($house['is_available'] != '1') {
                    throw new HttpException(Response::HTTP_BAD_REQUEST, 'Домик недоступен для бронирования');
                }
                $houseExists = true;
                $houseDetails = $house;
                break;
            }
        }

        if (!$houseExists) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Домик с указанным ID не найден');
        }

        $bookingData = [
            'house_id' => $houseId,
            'phone' => $phone,
            'comment' => $comment,
            'house_name' => $houseDetails['name'] ?? 'Неизвестный домик'
        ];

        $result = $this->csvService->addBooking($bookingData);

        if ($result) {
            return $this->json([
                'success' => true,
                'message' => 'Бронирование создано успешно',
                'booking_id' => $result
            ], Response::HTTP_CREATED);
        } else {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Ошибка при создании бронирования');
        }
    }

    /**
     * Метод изменения заявки на бронирование
     * PUT /api/bookings/{id}
     */
    #[Route('/api/bookings/{id}', name: 'update_booking', methods: ['PUT'])]
    public function updateBooking(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['comment']) || empty(trim($data['comment']))) {
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'Поле "comment" обязательно для заполнения и не может быть пустым'
            );
        }

        $newComment = trim($data['comment']);

        $bookings = $this->csvService->readBookings();
        $bookingExists = false;

        foreach ($bookings as $booking) {
            if (isset($booking['id']) && (int)$booking['id'] === $id) {
                $bookingExists = true;
                break;
            }
        }

        if (!$bookingExists) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Бронирование с ID ' . $id . ' не найдено');
        }

        $result = $this->csvService->updateBooking($id, $newComment);

        if ($result) {
            return $this->json([
                'success' => true,
                'message' => 'Комментарий бронирования обновлен успешно'
            ]);
        } else {
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Ошибка при обновлении бронирования');
        }
    }

    /**
     * Получить все бронирования
     * GET /api/bookings
     */
    #[Route('/api/bookings', name: 'get_bookings', methods: ['GET'])]
    public function getBookings(): JsonResponse
    {
        $bookings = $this->csvService->readBookings();

        return $this->json([
            'success' => true,
            'data' => $bookings,
            'count' => count($bookings)
        ]);
    }

    /**
     * Получить информацию о конкретном домике
     * GET /api/houses/{id}
     */
    #[Route('/api/houses/{id}', name: 'get_house', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $houses = $this->csvService->readHouses();

        foreach ($houses as $house) {
            if (isset($house['id']) && $house['id'] == $id) {
                return $this->json([
                    'success' => true,
                    'data' => $house
                ]);
            }
        }

        throw new HttpException(Response::HTTP_NOT_FOUND, 'Домик с ID ' . $id . ' не найден');
    }
}
