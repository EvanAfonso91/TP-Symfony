<?php

namespace App\Controller;

use App\Repository\VehicleRepository;
use App\Service\ReservationDateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AvailabilityController extends AbstractController
{
    #[Route('/availability', name: 'app_availability')]
    public function index(VehicleRepository $vehicleRepository): Response
    {
        return $this->render('availability/index.html.twig', [
            'vehicles' => $vehicleRepository->findAll(),
        ]);
    }

    #[Route('/api/vehicle/{id}/unavailable-dates', name: 'api_vehicle_unavailable_dates')]
    public function getUnavailableDates(
        int $id, 
        VehicleRepository $vehicleRepository,
        ReservationDateService $dateService
    ): JsonResponse {
        $vehicle = $vehicleRepository->find($id);
        
        if (!$vehicle) {
            return new JsonResponse([]);
        }

        $unavailableDates = $dateService->getUnavailableDates($vehicle);
        
        return new JsonResponse($unavailableDates);
    }
} 