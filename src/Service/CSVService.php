<?php

namespace App\Service;

use Carbon\Carbon;

class CSVService
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Чтение данных о домиках
     */
    public function readHouses(): array
    {
        $filePath = $this->projectDir . '/data/houses.csv';
        
        if (!file_exists($filePath)) {
            $this->createDefaultHousesFile($filePath);
        }
        
        return $this->readCSV($filePath);
    }

    /**
     * Чтение данных о бронированиях
     */
    public function readBookings(): array
    {
        $filePath = $this->projectDir . '/data/bookings.csv';
        
        if (!file_exists($filePath)) {
            $this->createEmptyBookingsFile($filePath);
            return [];
        }

        return $this->readCSV($filePath);
    }

    /**
     * Добавление нового бронирования
     */
    public function addBooking(array $bookingData): int
    {
        $filePath = $this->projectDir . '/data/bookings.csv';
        
        $bookings = $this->readBookings();
        
        $newId = 1;
        if (!empty($bookings)) {
            $ids = array_column($bookings, 'id');
            $ids = array_map('intval', $ids);
            
            $maxId = !empty($ids) ? max($ids) : 0;
            $newId = $maxId + 1;
        }
        
        $fullBookingData = [
            'id' => $newId,
            'house_id' => $bookingData['house_id'],
            'house_name' => $bookingData['house_name'],
            'phone' => $bookingData['phone'],
            'comment' => $bookingData['comment'],
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => 'active'
        ];

        $bookings[] = $fullBookingData;
        
        $this->writeCSV($filePath, $bookings);

        return $newId;
    }

    /**
     * Обновление комментария бронирования
     */
    public function updateBooking(int $bookingId, string $newComment): bool
    {
        $filePath = $this->projectDir . '/data/bookings.csv';
        $bookings = $this->readBookings();

        $updated = false;
        foreach ($bookings as &$booking) {
            // ИСПРАВЛЕНИЕ: приводим к int для сравнения
            if (isset($booking['id']) && (int)$booking['id'] === $bookingId) {
                $booking['comment'] = $newComment;
                $booking['updated_at'] = Carbon::now()->format('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            return $this->writeCSV($filePath, $bookings);
        }

        return false;
    }

    /**
     * Создание файла с тестовыми данными о домиках
     */
    private function createDefaultHousesFile(string $filePath): void
    {
        $defaultHouses = [
            [
                'id' => 1,
                'name' => 'Дом1',
                'beds' => 2,
                'amenities' => 'санузел,душевая кабина',
                'distance_to_sea' => 1,
                'price_per_night' => 5000,
                'is_available' => 1
            ],
            [
                'id' => 2,
                'name' => 'Дом2',
                'beds' => 4,
                'amenities' => '-',
                'distance_to_sea' => 3,
                'price_per_night' => 3000,
                'is_available' => 1
            ],
            [
                'id' => 3,
                'name' => 'Дом3',
                'beds' => 3,
                'amenities' => 'санузел,душевая кабина,кондиционер',
                'distance_to_sea' => 2,
                'price_per_night' => 7000,
                'is_available' => 1
            ],
            [
                'id' => 4,
                'name' => 'Дом4',
                'beds' => 2,
                'amenities' => 'санузел,душевая кабина,кухня,телевизор',
                'distance_to_sea' => 1,
                'price_per_night' => 10000,
                'is_available' => 1
            ],
            [
                'id' => 5,
                'name' => 'Дом5',
                'beds' => 6,
                'amenities' => 'санузел,душевая кабина',
                'distance_to_sea' => 4,
                'price_per_night' => 8000,
                'is_available' => 1
            ]
        ];

        $this->writeCSV($filePath, $defaultHouses);
    }

    /**
     * Создание пустого файла бронирований
     */
    private function createEmptyBookingsFile(string $filePath): void
    {
        $emptyBookings = [
            [
                'id' => 'id',
                'house_id' => 'house_id',
                'house_name' => 'house_name',
                'phone' => 'phone',
                'comment' => 'comment',
                'created_at' => 'created_at',
                'status' => 'status',
                'updated_at' => 'updated_at'
            ]
        ];

        $this->writeCSV($filePath, $emptyBookings);
    }

    /**
     * Чтение CSV файла
     */
    private function readCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $headers = fgetcsv($handle);
            
            if ($headers === FALSE) {
                fclose($handle);
                return [];
            }

            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($headers) === count($data)) {
                    $rows[] = array_combine($headers, $data);
                }
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Запись данных в CSV файл
     */
    private function writeCSV(string $filePath, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (($handle = fopen($filePath, 'w')) !== FALSE) {
            $headers = array_keys($data[0]);
            fputcsv($handle, $headers);
            
            foreach ($data as $row) {
                $orderedRow = [];
                foreach ($headers as $header) {
                    $orderedRow[$header] = $row[$header] ?? '';
                }
                fputcsv($handle, $orderedRow);
            }
            
            fclose($handle);
            
            if (filesize($filePath) > 0) {
                return true;
            }
        }

        return false;
    }
}