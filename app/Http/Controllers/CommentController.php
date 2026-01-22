<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\CommentImage;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $user = auth()->user();
        if ($task->user_id !== $user->id && $task->assigned_user_id !== $user->id) {
            return response()->json([
                'message' => 'Only task owner or assigned user can comment.',
            ], 403);
        }

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'comment' => $request->comment,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('comments', 'public');
                CommentImage::create([
                    'comment_id' => $comment->id,
                    'image_path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                ]);
            }
        }

        $comment->load(['user', 'images']);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment,
        ], 201);
    }

    public function index(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $comments = $task->comments()->with(['user', 'images'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function destroy(TaskComment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        foreach ($comment->images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
