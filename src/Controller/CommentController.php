<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Vehicle;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_comment_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur a une réservation terminée pour ce véhicule
        $hasCompletedReservation = $entityManager->getRepository('App\Entity\Reservation')
            ->findOneBy([
                'vehicle' => $vehicle,
                'user' => $this->getUser(),
                'status' => 'TERMINEE'
            ]);

        if (!$hasCompletedReservation) {
            $this->addFlash('error', 'Vous devez avoir loué ce véhicule pour pouvoir le commenter.');
            return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
        }

        // Vérifier si l'utilisateur a déjà commenté ce véhicule
        $existingComment = $entityManager->getRepository('App\Entity\Comment')
            ->findOneBy([
                'vehicle' => $vehicle,
                'user' => $this->getUser()
            ]);

        if ($existingComment) {
            $this->addFlash('error', 'Vous avez déjà commenté ce véhicule.');
            return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
        }

        $comment = new Comment();
        $comment->setVehicle($vehicle);
        $comment->setUser($this->getUser());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été publié avec succès !');
            return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
        }

        return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicle->getId()]);
    }

    #[Route('/comment/{id}/edit', name: 'app_comment_edit')]
    #[IsGranted('EDIT', subject: 'comment')]
    public function edit(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a été modifié avec succès.');
            
            return $this->redirectToRoute('app_vehicle_show', [
                'id' => $comment->getVehicle()->getId()
            ]);
        }

        return $this->render('comment/edit.html.twig', [
            'comment' => $comment,
            'form' => $form
        ]);
    }

    #[Route('/comment/{id}', name: 'app_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $vehicleId = $comment->getVehicle()->getId();
            $entityManager->remove($comment);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le commentaire a été supprimé avec succès.');
            
            return $this->redirectToRoute('app_vehicle_show', ['id' => $vehicleId]);
        }

        return $this->redirectToRoute('app_vehicle_show', [
            'id' => $comment->getVehicle()->getId()
        ]);
    }
} 