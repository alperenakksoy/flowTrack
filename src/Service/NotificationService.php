<?php

namespace App\Service;

use App\Entity\Goal;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        #[Autowire(env: 'MAIL_SENDER')] private readonly string $mailSender,
        #[Autowire(env: 'MAIL_FROM')] private readonly string $mailFrom,
    ) {
    }

    public function sendTaskAssignedEmail(Task $task, User $user): void
    {
        try {
            $message = (new Email())
                ->from($this->mailFrom)
                ->to($user->getEmail())
                ->subject('New Task Assigned: '.$task->getTitle())
                ->html($this->twig->render('email/task_assigned.html.twig', [
                    'task' => $task,
                    'user' => $user,
                ]));

            $this->mailer->send($message);
        } catch (\Exception $e) {
            // Log error but don't break the task creation
            // You can add logger here later
            error_log('Failed to send task assignment email: '.$e->getMessage());
        }
    }

    public function sendTaskCompletedEmail(Task $task, User $createdBy): void
    {
        try {
            $message = (new Email())
                ->from($this->mailFrom)
                ->to($createdBy->getEmail())
                ->subject('Task Completed: '.$task->getTitle())
                ->html($this->twig->render('email/task_completed.html.twig', [
                    'task' => $task,
                    'user' => $createdBy,
                    'completedBy' => $task->getAssignedTo(),
                ]));

            $this->mailer->send($message);
        } catch (\Exception $e) {
            error_log('Failed to send task completion email: '.$e->getMessage());
        }
    }

    public function sendDeadlineReminderEmail(Task $task, User $user): void
    {
        try {
            $message = (new Email())
                ->from($this->mailFrom)
                ->to($user->getEmail())
                ->subject('⚠️ Task Deadline Reminder: '.$task->getTitle())
                ->html($this->twig->render('email/deadline_reminder.html.twig', [
                    'task' => $task,
                    'user' => $user,
                ]));

            $this->mailer->send($message);
        } catch (\Exception $e) {
            error_log('Failed to send deadline reminder email: '.$e->getMessage());
        }
    }

    public function sendGoalAssignedEmail(Goal $goal, User $user): void
    {
        try {
            $message = (new Email())
                ->from($this->mailFrom)
                ->to($user->getEmail())
                ->subject('New Goal Assigned for Week '.$goal->getWeek())
                ->html($this->twig->render('email/goal_assigned.html.twig', [
                    'goal' => $goal,
                    'user' => $user,
                ]));

            $this->mailer->send($message);
        } catch (\Exception $e) {
            error_log('Failed to send goal assignment email: '.$e->getMessage());
        }
    }
}
