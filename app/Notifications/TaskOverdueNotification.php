<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = $this->task->due_date ? now()->diffInDays($this->task->due_date) : 0;

        return (new MailMessage)
            ->subject('⚠️ Overdue Task: ' . $this->task->title)
            ->line('This task is overdue!')
            ->line('Task: ' . $this->task->title)
            ->line('Project: ' . $this->task->project->name)
            ->line('Priority: ' . ucfirst($this->task->priority))
            ->line('Due Date: ' . ($this->task->due_date ? $this->task->due_date->format('Y-m-d') : 'Not set'))
            ->line('Days Overdue: ' . $daysOverdue)
            ->line('Status: ' . ucfirst(str_replace('_', ' ', $this->task->status)))
            ->action('View Task', url('/tasks/' . $this->task->id))
            ->line('Please update the task status or complete it as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'due_date' => $this->task->due_date?->toDateString(),
            'days_overdue' => $this->task->due_date ? now()->diffInDays($this->task->due_date) : 0,
        ];
    }
}
