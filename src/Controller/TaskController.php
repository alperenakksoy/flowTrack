<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Security\Permissions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MANAGER')]
class TaskController extends AbstractController
{
    #[Route('/task/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function create(EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permissions::CREATE, Task::class);

        return $this->update($em, new Task(), $request);
    }

    #[Route('/task/edit/{id}', name: 'task_edit', methods: ['GET', 'POST'])]
    public function update(EntityManagerInterface $em, Task $task, Request $request): Response
    {
        $manager = $this->getUser();

        if ($task->getId()) {
            $this->denyAccessUnlessGranted(Permissions::EDIT, $task);
        }

        $form = $this->createForm(TaskType::class, $task, [
            'manager_team' => $manager->getTeam(),
        ]);
        $form->add('save', SubmitType::class, ['label' => $task->getId() ? 'Update' : 'Create']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$task->getCreatedBy()) {
                $task->setCreatedBy($this->getUser());
            }
            $task->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/task/{id}', name: 'task_show')]
    public function show(?Task $task, EntityManagerInterface $em, Request $request): Response
    {
        $manager = $this->getUser();
        if (null === $task) {
            throw $this->createNotFoundException('No Post found');
        }
        $this->denyAccessUnlessGranted(Permissions::VIEW, $task);

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'manager' => $manager,
        ]);
    }

    #[Route('/task/delete/{id}', name: 'task_delete')]
    public function delete(Task $task, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(Permissions::DELETE, $task);
        $em->remove($task);
        $em->flush();

        return $this->redirectToRoute('dashboard');
    }

//    public function index(EntityManagerInterface $em, Request $request,TaskRepository $taskRepository): Response
//    {
//
//    }
}
