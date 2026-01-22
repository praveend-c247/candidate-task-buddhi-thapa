<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskImage;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Task::with(['project', 'owner', 'assignedUser', 'images'])
            ->where(function ($q) use ($user) {
                $q->whereHas('project', function ($projectQuery) use ($user) {
                    $projectQuery->where('user_id', $user->id);
                })
                ->orWhere('assigned_user_id', $user->id)
                ->orWhere('user_id', $user->id);
            });

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $perPage = $request->get('per_page', 15);
        $tasks = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $project = Project::findOrFail($request->project_id);
        
        if ($project->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You do not have permission to create tasks in this project.',
            ], 403);
        }

        $task = Task::create([
            'project_id' => $request->project_id,
            'user_id' => Auth::id(),
            'assigned_user_id' => $request->assigned_user_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'status' => $request->status ?? 'pending',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('tasks', 'public');
                TaskImage::create([
                    'task_id' => $task->id,
                    'image_path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                ]);
            }
        }

        if ($task->assigned_user_id && $task->assigned_user_id !== Auth::id()) {
            $assignedUser = $task->assignedUser;
            $taskOwner = $task->owner;
            
            $assignedUser->notify(new TaskAssignedNotification($task));
            $taskOwner->notify(new TaskAssignedNotification($task));
        }

        $task->load(['project', 'owner', 'assignedUser', 'images']);

        return response()->json([
            'message' => 'Task created successfully',
            'data' => $task,
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['project', 'owner', 'assignedUser', 'images', 'comments.user', 'comments.images']);

        return response()->json([
            'data' => $task,
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task->update($request->only([
            'title', 'description', 'priority', 'due_date', 'status'
        ]));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('tasks', 'public');
                TaskImage::create([
                    'task_id' => $task->id,
                    'image_path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                ]);
            }
        }

        $task->load(['project', 'owner', 'assignedUser', 'images']);

        return response()->json([
            'message' => 'Task updated successfully',
            'data' => $task,
        ]);
    }

    public function assign(AssignTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('assign', $task);

        $oldAssignedUserId = $task->assigned_user_id;
        $task->assigned_user_id = $request->assigned_user_id;
        $task->save();

        if ($task->assigned_user_id && $task->assigned_user_id !== $oldAssignedUserId) {
            $assignedUser = $task->assignedUser;
            $taskOwner = $task->owner;
            
            $assignedUser->notify(new TaskAssignedNotification($task));
            $taskOwner->notify(new TaskAssignedNotification($task));
        }

        $task->load(['project', 'owner', 'assignedUser', 'images']);

        return response()->json([
            'message' => 'Task assigned successfully',
            'data' => $task,
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        foreach ($task->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}
