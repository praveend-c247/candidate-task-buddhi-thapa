<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $reminderType
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reminderMessages = [
            '7_days' => 'This task is due in 7 days.',
            '24_hours' => 'This task is due in 24 hours.',
            '12_hours' => 'This task is due in 12 hours.',
        ];

        $message = (new MailMessage)
            ->subject('Task Reminder: ' . $this->task->title)
            ->line($reminderMessages[$this->reminderType] ?? 'This is a reminder for your task.')
            ->line('Task: ' . $this->task->title)
            ->line('Project: ' . $this->task->project->name)
            ->line('Priority: ' . ucfirst($this->task->priority))
            ->line('Due Date: ' . ($this->task->due_date ? $this->task->due_date->format('Y-m-d H:i') : 'Not set'))
            ->line('Status: ' . ucfirst(str_replace('_', ' ', $this->task->status)))
            ->action('View Task', url('/tasks/' . $this->task->id))
            ->line('Please complete this task before the due date.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'reminder_type' => $this->reminderType,
            'due_date' => $this->task->due_date?->toDateString(),
        ];
    }
}
