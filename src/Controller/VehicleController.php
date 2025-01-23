<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Form\VehicleType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Form\VehicleFilterType;
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\ReservationDateService;

#[Route('/vehicle')]
class VehicleController extends AbstractController
{
    // Liste tous les véhicules avec leur statut de disponibilité
    #[Route('/', name: 'app_vehicle_index', methods: ['GET'])]
    public function index(Request $request, VehicleRepository $vehicleRepository, ReservationDateService $dateService): Response
    {
        $filterForm = $this->createForm(VehicleFilterType::class);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            // Récupérer les données du filtre
            $filterData = $filterForm->getData();
            // Utiliser les filtres pour récupérer les véhicules
            $vehicles = $vehicleRepository->findByFilters($filterData);
        } else {
            // Si pas de filtre, récupérer tous les véhicules
            $vehicles = $vehicleRepository->findAll();
        }

        foreach ($vehicles as $vehicle) {
            $vehicle->setIsAvailable($dateService->isVehicleAvailableToday($vehicle));
        }

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
            'filterForm' => $filterForm->createView()
        ]);
    }

    // Création d'un nouveau véhicule (réservé aux admins)
    #[Route('/new', name: 'app_vehicle_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $vehicle = new Vehicle();
        $vehicle->setCreatedAt(new \DateTime());
        
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                $vehicle->setImage($imageFileName);
            }

            $entityManager->persist($vehicle);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/new.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    // Affiche les détails d'un véhicule et gère la possibilité de commenter
    #[Route('/{id}', name: 'app_vehicle_show', methods: ['GET'])]
    public function show(Vehicle $vehicle, EntityManagerInterface $entityManager, ReservationDateService $dateService): Response
    {
        $vehicle->setIsAvailable($dateService->isVehicleAvailableToday($vehicle));
        
        $canComment = false;
        
        if ($this->isGranted('ROLE_USER')) {
            // Vérifie si l'utilisateur a une réservation terminée
            $hasCompletedReservation = $entityManager->getRepository('App\Entity\Reservation')
                ->findOneBy([
                    'vehicle' => $vehicle,
                    'user' => $this->getUser(),
                    'status' => 'TERMINEE'
                ]);

            // Vérifie si l'utilisateur n'a pas déjà commenté
            $existingComment = $entityManager->getRepository('App\Entity\Comment')
                ->findOneBy([
                    'vehicle' => $vehicle,
                    'user' => $this->getUser()
                ]);

            $canComment = $hasCompletedReservation && !$existingComment;
        }

        $commentForm = $this->createForm(CommentType::class, new Comment(), [
            'action' => $this->generateUrl('app_comment_new', ['id' => $vehicle->getId()])
        ]);

        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'canComment' => $canComment,
            'commentForm' => $commentForm,
        ]);
    }

    // Modification d'un véhicule existant (réservé aux admins)
    #[Route('/{id}/edit', name: 'app_vehicle_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageFileName = $fileUploader->upload($imageFile);
                // Supprime l'ancienne image si elle existe
                if ($vehicle->getImage()) {
                    $oldImage = $fileUploader->getTargetDirectory().'/'.$vehicle->getImage();
                    if (file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                $vehicle->setImage($imageFileName);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    // Suppression d'un véhicule (réservé aux admins)
    #[Route('/{id}', name: 'app_vehicle_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vehicle->getId(), $request->request->get('_token'))) {
            $entityManager->remove($vehicle);
            $entityManager->flush();
            $this->addFlash('success', 'Le véhicule a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
    }
}
