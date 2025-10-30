<?php

namespace App\Controller;

use App\Service\CSVService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

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
        try {
            $houses = $this->csvService->readHouses();
            $availableHouses = array_filter($houses, function($house) {
                return isset($house['is_available']) && $house['is_available'] == '1';
            });

            return $this->json([
                'success' => true,
                'data' => array_values($availableHouses),
                'count' => count($availableHouses)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Ошибка при получении списка домиков: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Метод создания заявки на бронирование домика
     * POST /api/bookings
     */
    #[Route('/api/bookings', name: 'create_booking', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['phone']) || empty($data['phone'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Поле "phone" обязательно для заполнения'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['house_id']) || empty($data['house_id'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Поле "house_id" обязательно для заполнения'
                ], Response::HTTP_BAD_REQUEST);
            }

            $phone = $data['phone'];
            $houseId = (int)$data['house_id'];
            $comment = $data['comment'] ?? '';

            if (!preg_match('/^\+?[0-9\s\-\(\)]{10,}$/', $phone)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Неверный формат номера телефона'
                ], Response::HTTP_BAD_REQUEST);
            }

            $houses = $this->csvService->readHouses();
            $houseExists = false;
            $houseDetails = null;

            foreach ($houses as $house) {
                if (isset($house['id']) && $house['id'] == $houseId) {
                    if ($house['is_available'] != '1') {
                        return $this->json([
                            'success' => false,
                            'error' => 'Домик недоступен для бронирования'
                        ], Response::HTTP_BAD_REQUEST);
                    }
                    $houseExists = true;
                    $houseDetails = $house;
                    break;
                }
            }

            if (!$houseExists) {
                return $this->json([
                    'success' => false,
                    'error' => 'Домик с указанным ID не найден'
                ], Response::HTTP_NOT_FOUND);
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
                return $this->json([
                    'success' => false,
                    'error' => 'Ошибка при создании бронирования'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Ошибка при создании бронирования: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Метод изменения заявки на бронирование
     * PUT /api/bookings/{id}
     */
    #[Route('/api/bookings/{id}', name: 'update_booking', methods: ['PUT'])]
    public function updateBooking(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['comment']) || empty(trim($data['comment']))) {
                return $this->json([
                    'success' => false,
                    'error' => 'Поле "comment" обязательно для заполнения и не может быть пустым'
                ], Response::HTTP_BAD_REQUEST);
            }

            $newComment = trim($data['comment']);

            $bookings = $this->csvService->readBookings();
            $bookingExists = false;
            
            foreach ($bookings as $booking) {
                if (isset($booking['id']) && $booking['id'] == $id) {
                    $bookingExists = true;
                    break;
                }
            }

            if (!$bookingExists) {
                return $this->json([
                    'success' => false,
                    'error' => 'Бронирование с ID ' . $id . ' не найдено'
                ], Response::HTTP_NOT_FOUND);
            }

            $result = $this->csvService->updateBooking($id, $newComment);

            if ($result) {
                return $this->json([
                    'success' => true,
                    'message' => 'Комментарий бронирования обновлен успешно'
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'error' => 'Ошибка при обновлении бронирования'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Ошибка при обновлении бронирования: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Получить все бронирования
     * GET /api/bookings
     */
    #[Route('/api/bookings', name: 'get_bookings', methods: ['GET'])]
    public function getBookings(): JsonResponse
    {
        try {
            $bookings = $this->csvService->readBookings();

            return $this->json([
                'success' => true,
                'data' => $bookings,
                'count' => count($bookings)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Ошибка при получении списка бронирований: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Получить информацию о конкретном домике
     * GET /api/houses/{id}
     */
    #[Route('/api/houses/{id}', name: 'get_house', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        try {
            $houses = $this->csvService->readHouses();
            
            foreach ($houses as $house) {
                if (isset($house['id']) && $house['id'] == $id) {
                    return $this->json([
                        'success' => true,
                        'data' => $house
                    ]);
                }
            }

            return $this->json([
                'success' => false,
                'error' => 'Домик с ID ' . $id . ' не найден'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Ошибка при получении информации о домике: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}