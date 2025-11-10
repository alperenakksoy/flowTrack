<?php

namespace App\Controller;

use App\Entity\Goal;
use App\Entity\User;
use App\Form\GoalType;
use App\Security\Permissions;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GoalController extends AbstractController
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[Route('/goal/create', name: 'goal_create')]
    #[IsGranted('ROLE_MANAGER')]
    public function create(EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permissions::CREATE, Goal::class);

        return $this->update($em, new Goal(), $request);
    }

    #[Route('/goal/edit/{id}', name: 'goal_edit')]
    #[IsGranted('ROLE_MANAGER')]
    public function update(EntityManagerInterface $em, Goal $goal, Request $request): Response
    {
        /** @var User $manager */
        $manager = $this->getUser();

        if (!$goal->getId()) {
            $this->denyAccessUnlessGranted(Permissions::EDIT, Goal::class);
        }
        if (!$manager) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(GoalType::class, $goal, [
            'manager_team' => $manager->getTeam(),
        ]);
        $form->add('save', SubmitType::class, [
            'label' => $goal->getId() ? 'Update' : 'Create',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$goal->getCreatedBy()) {
                $goal->setCreatedBy($this->getUser());
            }
            $goal->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($goal);
            $em->flush();

            if (!$goal->getId()) {
                $this->notificationService->sendGoalAssignedEmail($goal, $goal->getEmployee());
            }

            return $this->redirectToRoute('goal_show', ['id' => $goal->getId()]);
        }

        return $this->render('goal/create.html.twig', [
            'form' => $form->createView(),
            'goal' => $goal,
        ]);
    }

    #[Route('/goal/delete/{id}', name: 'goal_delete')]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Goal $goal, EntityManagerInterface $manager): Response
    {
        $manager->remove($goal);
        $manager->flush();

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/goal/{id}', name: 'goal_show')]
    public function show(?Goal $goal): Response
    {
        if (!$goal) {
            throw $this->createNotFoundException('Goal not found');
        }

        return $this->render('goal/show.html.twig', [
            'goal' => $goal,
        ]);
    }
}
