<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskImage;
use App\Models\CommentImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $totalProjects = $user->projects()->count();

        $totalTasks = Task::where(function ($query) use ($user) {
            $query->whereHas('project', function ($projectQuery) use ($user) {
                $projectQuery->where('user_id', $user->id);
            })
            ->orWhere('assigned_user_id', $user->id)
            ->orWhere('user_id', $user->id);
        })->count();

        $completedTasks = Task::where(function ($query) use ($user) {
            $query->whereHas('project', function ($projectQuery) use ($user) {
                $projectQuery->where('user_id', $user->id);
            })
            ->orWhere('assigned_user_id', $user->id)
            ->orWhere('user_id', $user->id);
        })->where('status', 'completed')->count();

        $completedTaskPercentage = $totalTasks > 0 
            ? round(($completedTasks / $totalTasks) * 100, 2) 
            : 0;

        $overdueTasks = Task::where(function ($query) use ($user) {
            $query->whereHas('project', function ($projectQuery) use ($user) {
                $projectQuery->where('user_id', $user->id);
            })
            ->orWhere('assigned_user_id', $user->id)
            ->orWhere('user_id', $user->id);
        })
        ->where('due_date', '<', now())
        ->where('status', '!=', 'completed')
        ->select('priority', DB::raw('count(*) as count'))
        ->groupBy('priority')
        ->get()
        ->pluck('count', 'priority')
        ->toArray();

        $tasksDueIn7Days = Task::where(function ($query) use ($user) {
            $query->whereHas('project', function ($projectQuery) use ($user) {
                $projectQuery->where('user_id', $user->id);
            })
            ->orWhere('assigned_user_id', $user->id)
            ->orWhere('user_id', $user->id);
        })
        ->whereBetween('due_date', [now(), now()->addDays(7)])
        ->where('status', '!=', 'completed')
        ->with(['project', 'assignedUser'])
        ->get();

        $tasksAssignedToMe = Task::where('assigned_user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->with(['project', 'owner'])
            ->orderBy('due_date', 'asc')
            ->get();

        $totalComments = TaskComment::whereHas('task', function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('project', function ($projectQuery) use ($user) {
                    $projectQuery->where('user_id', $user->id);
                })
                ->orWhere('assigned_user_id', $user->id)
                ->orWhere('user_id', $user->id);
            });
        })->count();

        $totalTaskImages = TaskImage::whereHas('task', function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('project', function ($projectQuery) use ($user) {
                    $projectQuery->where('user_id', $user->id);
                })
                ->orWhere('assigned_user_id', $user->id)
                ->orWhere('user_id', $user->id);
            });
        })->count();

        $totalCommentImages = CommentImage::whereHas('comment.task', function ($query) use ($user) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('project', function ($projectQuery) use ($user) {
                    $projectQuery->where('user_id', $user->id);
                })
                ->orWhere('assigned_user_id', $user->id)
                ->orWhere('user_id', $user->id);
            });
        })->count();

        $totalImages = $totalTaskImages + $totalCommentImages;

        return response()->json([
            'data' => [
                'total_projects' => $totalProjects,
                'total_tasks' => $totalTasks,
                'completed_task_percentage' => $completedTaskPercentage,
                'overdue_tasks_by_priority' => [
                    'low' => $overdueTasks['low'] ?? 0,
                    'medium' => $overdueTasks['medium'] ?? 0,
                    'high' => $overdueTasks['high'] ?? 0,
                ],
                'tasks_due_in_next_7_days' => $tasksDueIn7Days,
                'tasks_assigned_to_me' => $tasksAssignedToMe,
                'total_comments_count' => $totalComments,
                'total_uploaded_images_count' => $totalImages,
            ],
        ]);
    }
}
