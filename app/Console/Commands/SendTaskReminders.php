<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'tasks:send-reminders';

    protected $description = 'Send task reminders based on due dates (runs at 10:00 AM)';

    public function handle()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        
        if ($now->isWeekend()) {
            $this->info('Today is a weekend. Reminders are only sent on working days.');
            return 0;
        }

        $tasks = Task::whereNotNull('due_date')
            ->where('status', '!=', 'completed')
            ->with(['assignedUser', 'owner', 'project'])
            ->get();

        $remindersSent = 0;
        $overdueSent = 0;

        foreach ($tasks as $task) {
            if (!$task->due_date) {
                continue;
            }

            $dueDate = Carbon::parse($task->due_date)->startOfDay();
            $daysUntilDue = (int) $today->diffInDays($dueDate, false);

            if ($dueDate->isPast()) {
                $this->sendOverdueNotification($task);
                $overdueSent++;
                continue;
            }

            if ($daysUntilDue === 7) {
                if ($this->sendReminder($task, '7_days', $dueDate)) {
                    $remindersSent++;
                }
            }

            if ($daysUntilDue === 1 && $dueDate->isTomorrow()) {
                if ($this->sendReminder($task, '24_hours', $dueDate)) {
                    $remindersSent++;
                }
            }

            $hoursUntilDue = (int) $now->diffInHours($dueDate, false);
            if ($hoursUntilDue <= 12 && $hoursUntilDue > 11) {
                if ($this->sendReminder($task, '12_hours', $dueDate)) {
                    $remindersSent++;
                }
            }
        }

        $this->info("Sent {$remindersSent} reminders and {$overdueSent} overdue notifications.");

        return 0;
    }

    protected function sendReminder(Task $task, string $reminderType, Carbon $dueDate): bool
    {
        $reminderDate = $this->getReminderDate($dueDate, $reminderType);
        $today = Carbon::now()->startOfDay();

        if (!$reminderDate->isSameDay($today)) {
            return false;
        }

        $sent = false;

        if ($task->assignedUser) {
            try {
                $task->assignedUser->notify(new TaskReminderNotification($task, $reminderType));
                $sent = true;
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to assigned user for task {$task->id}: " . $e->getMessage());
            }
        }

        if ($task->owner && $task->owner->id !== $task->assignedUser?->id) {
            try {
                $task->owner->notify(new TaskReminderNotification($task, $reminderType));
                $sent = true;
            } catch (\Exception $e) {
                $this->error("Failed to send reminder to owner for task {$task->id}: " . $e->getMessage());
            }
        }

        return $sent;
    }

    protected function sendOverdueNotification(Task $task): void
    {
        if ($task->assignedUser) {
            $task->assignedUser->notify(new TaskOverdueNotification($task));
        }

        if ($task->owner && $task->owner->id !== $task->assignedUser?->id) {
            $task->owner->notify(new TaskOverdueNotification($task));
        }
    }

    protected function getReminderDate(Carbon $dueDate, string $reminderType): Carbon
    {
        $reminderDate = match ($reminderType) {
            '7_days' => $dueDate->copy()->subDays(7),
            '24_hours' => $dueDate->copy()->subDay(),
            '12_hours' => $dueDate->copy()->subHours(12),
            default => $dueDate,
        };

        if ($reminderDate->isSaturday()) {
            $reminderDate->subDay();
        } elseif ($reminderDate->isSunday()) {
            $reminderDate->subDays(2);
        }

        return $reminderDate->startOfDay();
    }
}
