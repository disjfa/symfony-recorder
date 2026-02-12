<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Message\DeleteTaskWithVideo;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', name: 'app_tasks', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findAllOrderedByCreated();

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/{id}', name: 'app_task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Task updated successfully');

            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form,
            'task' => $task,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['GET'])]
    public function delete(Task $task): Response
    {
        // Dispatch async message to delete task and associated video
        $this->messageBus->dispatch(new DeleteTaskWithVideo($task->getId()));

        return $this->redirectToRoute('app_tasks');
    }

    #[Route('/{id}/update-status', name: 'api_task_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Task $task): Response
    {
        try {
            $status = $request->request->get('status');

            if (!in_array($status, Task::getValidStatuses())) {
                return new JsonResponse(['error' => 'Invalid status'], Response::HTTP_BAD_REQUEST);
            }

            $task->setStatus($status);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Task status updated',
                'status' => $task->getStatus(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Failed to update task: '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
