<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Vehicle;
use App\Service\ReservationDateService;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $reservations = $reservationRepository->findAll();
        } else {
            $reservations = $reservationRepository->findBy(['user' => $this->getUser()]);
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ReservationDateService $dateService
    ): Response {
        $reservation = new Reservation();
        $unavailableDates = [];
        
        // Pré-remplir le véhicule si l'ID est passé dans l'URL
        $vehicleId = null;
        if ($request->query->has('vehicle')) {
            $vehicle = $entityManager->getRepository(Vehicle::class)->find($request->query->get('vehicle'));
            if ($vehicle) {
                $reservation->setVehicle($vehicle);
                $vehicleId = $vehicle->getId();
                $unavailableDates = $dateService->getUnavailableDates($vehicle);
            }
        }
        
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérification supplémentaire des dates
            $startDate = $reservation->getStartDate();
            $endDate = $reservation->getEndDate();
            $vehicle = $reservation->getVehicle();
            
            // Vérifier si les dates sont disponibles
            $unavailableDates = $dateService->getUnavailableDates($vehicle);
            $endDatePlusOne = (new \DateTime($endDate->format('Y-m-d')))->add(new \DateInterval('P1D'));
            $period = new \DatePeriod(
                $startDate,
                new \DateInterval('P1D'),
                $endDatePlusOne
            );
            
            foreach ($period as $date) {
                if (in_array($date->format('Y-m-d'), $unavailableDates)) {
                    $this->addFlash('error', 'Ces dates ne sont pas disponibles pour ce véhicule. <a href="' . $this->generateUrl('app_availability', ['vehicle' => $vehicle->getId()]) . '" class="alert-link">Voir les disponibilités</a>');
                    return $this->redirectToRoute('app_reservation_new', [
                        'vehicle' => $vehicle->getId()
                    ]);
                }
            }

            $reservation->setUser($this->getUser());
            $reservation->setStatus('EN_ATTENTE');
            
            // Calcul du prix total
            $days = $startDate->diff($endDate)->days + 1;
            $totalPrice = $days * $vehicle->getPricePerDay();
            $reservation->setTotalPrice($totalPrice);

            $entityManager->persist($reservation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réservation a été créée avec succès.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
            'unavailableDates' => json_encode($unavailableDates),
            'vehicleId' => $vehicleId ?? 'null'
        ]);
    }

    #[Route('/status/{id}', name: 'app_reservation_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function changeStatus(
        Request $request, 
        Reservation $reservation, 
        EntityManagerInterface $entityManager
    ): Response {
        $newStatus = $request->request->get('status');
        $vehicle = $reservation->getVehicle();

        // Mise à jour du statut de la réservation
        $reservation->setStatus($newStatus);

        // Mise à jour de la disponibilité du véhicule
        if ($newStatus === Reservation::STATUS_CONFIRMED) {
            $vehicle->setIsAvailable(false);
        } elseif ($newStatus === Reservation::STATUS_COMPLETED || $newStatus === Reservation::STATUS_CANCELLED) {
            $vehicle->setIsAvailable(true);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Le statut de la réservation a été mis à jour.');

        return $this->redirectToRoute('app_reservation_index');
    }

    #[Route('/edit/{id}', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est propriétaire de la réservation ou admin
        if (!$this->isGranted('ROLE_ADMIN') && $reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette réservation.');
        }

        // Vérifier si la réservation peut être modifiée
        if ($reservation->getStatus() === 'TERMINEE') {
            $this->addFlash('error', 'Impossible de modifier une réservation terminée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        if ($reservation->getStartDate() <= new \DateTime()) {
            $this->addFlash('error', 'Impossible de modifier une réservation qui a déjà commencé.');
            return $this->redirectToRoute('app_reservation_index');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->calculateTotalPrice();
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été modifiée avec succès.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/cancel/{id}', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            // Vérifier que l'utilisateur est propriétaire de la réservation ou admin
            if (!$this->isGranted('ROLE_ADMIN') && $reservation->getUser() !== $this->getUser()) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette réservation.');
            }

            // Vérifier si la réservation peut être annulée
            if ($reservation->getStatus() === 'TERMINEE') {
                $this->addFlash('error', 'Impossible d\'annuler une réservation terminée.');
                return $this->redirectToRoute('app_reservation_index');
            }

            if ($reservation->getStartDate() <= new \DateTime()) {
                $this->addFlash('error', 'Impossible d\'annuler une réservation qui a déjà commencé.');
                return $this->redirectToRoute('app_reservation_index');
            }

            $reservation->setStatus('ANNULEE');
            $vehicle = $reservation->getVehicle();
            $vehicle->setIsAvailable(true);
            
            $entityManager->flush();

            $this->addFlash('success', 'La réservation a été annulée avec succès.');
        }

        return $this->redirectToRoute('app_reservation_index');
    }
} 