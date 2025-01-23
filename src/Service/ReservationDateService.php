<?php

namespace App\Service;

use App\Entity\Vehicle;
use App\Repository\ReservationRepository;

class ReservationDateService
{
    private $reservationRepository;

    public function __construct(ReservationRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    public function getUnavailableDates(Vehicle $vehicle): array
    {
        $reservations = $this->reservationRepository->findBy([
            'vehicle' => $vehicle,
            'status' => ['EN_ATTENTE', 'CONFIRMEE']
        ]);

        $unavailableDates = [];
        foreach ($reservations as $reservation) {
            $startDate = (clone $reservation->getStartDate())->modify('-1 day');
            $endDate = (clone $reservation->getEndDate()->modify('-1 day'));
            
            $period = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDate->modify('+1 day')
            );

            foreach ($period as $date) {
                $unavailableDates[] = $date->format('Y-m-d');
            }
        }

        return array_unique($unavailableDates);
    }

    public function isVehicleAvailableToday(Vehicle $vehicle): bool
    {
        $today = (new \DateTime())->format('Y-m-d');
        $unavailableDates = $this->getUnavailableDates($vehicle);
        
        return !in_array($today, $unavailableDates);
    }
} 