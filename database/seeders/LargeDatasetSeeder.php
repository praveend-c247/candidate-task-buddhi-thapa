<?php

namespace Database\Seeders;

use App\Models\CommentImage;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LargeDatasetSeeder extends Seeder
{
    private $priorities = ['low', 'medium', 'high'];
    private $statuses = ['pending', 'in_progress', 'completed'];
    private $mimeTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    private $imageExtensions = ['jpg', 'png', 'jpeg', 'gif'];

    public function run(): void
    {
        $recordCount = (int) (env('SEED_RECORD_COUNT', 100000));
        
        $this->output("Starting large dataset seeding with {$recordCount} records...");
        $startTime = microtime(true);

        $this->output("Creating 100 users...");
        $users = $this->createUsers(100);
        $userIds = $users->pluck('id')->toArray();

        $this->output("Creating 1000 projects...");
        $projects = $this->createProjects($users, 1000);
        $projectIds = $projects->pluck('id')->toArray();

        $this->output("Creating {$recordCount} tasks in chunks of 1000...");
        $this->createTasksInChunks($projectIds, $userIds, $recordCount);

        $this->output("Creating task images (30% of tasks)...");
        $this->createTaskImages($recordCount);

        $this->output("Creating comments (50% of tasks)...");
        $this->createComments($recordCount);

        $this->output("Creating comment images (20% of tasks)...");
        $this->createCommentImages($recordCount);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->output("Large dataset seeding completed in {$duration} seconds!");
        $this->output("Total records created:");
        $this->output("  - Users: 100");
        $this->output("  - Projects: 1000");
        $this->output("  - Tasks: {$recordCount}");
        $this->output("  - Task Images: " . (int)($recordCount * 0.3));
        $this->output("  - Comments: " . (int)($recordCount * 0.5));
        $this->output("  - Comment Images: " . (int)($recordCount * 0.2));
    }

    private function output(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            echo $message . PHP_EOL;
        }
    }

    private function createUsers(int $count): \Illuminate\Support\Collection
    {
        $users = [];
        $password = Hash::make('password');
        $now = now();

        for ($i = 1; $i <= $count; $i++) {
            $users[] = [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => $password,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('users')->insert($users);
        return User::whereIn('email', array_column($users, 'email'))->get();
    }

    private function createProjects($users, int $count): \Illuminate\Support\Collection
    {
        $projects = [];
        $userIds = $users->pluck('id')->toArray();
        $now = now();

        for ($i = 1; $i <= $count; $i++) {
            $projects[] = [
                'user_id' => $userIds[array_rand($userIds)],
                'name' => "Project {$i}",
                'description' => "Description for project {$i}",
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('projects')->insert($projects);
        return Project::whereIn('name', array_column($projects, 'name'))->get();
    }

    private function createTasksInChunks(array $projectIds, array $userIds, int $totalRecords): void
    {
        $chunkSize = 1000;
        $chunks = ceil($totalRecords / $chunkSize);
        $now = now();

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $tasks = [];
            $start = $chunk * $chunkSize;
            $end = min($start + $chunkSize, $totalRecords);

            for ($i = $start; $i < $end; $i++) {
                $dueDateOffset = rand(-30, 30);
                $dueDate = now()->addDays($dueDateOffset);

                $tasks[] = [
                    'project_id' => $projectIds[array_rand($projectIds)],
                    'user_id' => $userIds[array_rand($userIds)],
                    'assigned_user_id' => rand(0, 10) > 2 ? $userIds[array_rand($userIds)] : null,
                    'title' => "Task " . ($i + 1),
                    'description' => "Description for task " . ($i + 1),
                    'priority' => $this->priorities[array_rand($this->priorities)],
                    'due_date' => $dueDate,
                    'status' => $this->statuses[array_rand($this->statuses)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('tasks')->insert($tasks);
            
            if (($chunk + 1) % 10 == 0 || ($chunk + 1) == $chunks) {
                $this->output("  Progress: " . ($chunk + 1) . "/{$chunks} chunks (" . ($end) . "/{$totalRecords} tasks)");
            }
        }
    }

    private function createTaskImages(int $taskCount): void
    {
        $imageCount = (int) ($taskCount * 0.3);
        $chunkSize = 1000;
        $chunks = ceil($imageCount / $chunkSize);
        $now = now();

        $taskIds = DB::table('tasks')->pluck('id')->toArray();

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $images = [];
            $start = $chunk * $chunkSize;
            $end = min($start + $chunkSize, $imageCount);

            for ($i = $start; $i < $end; $i++) {
                $mimeType = $this->mimeTypes[array_rand($this->mimeTypes)];
                $ext = $this->imageExtensions[array_rand($this->imageExtensions)];

                $images[] = [
                    'task_id' => $taskIds[array_rand($taskIds)],
                    'image_path' => "tasks/image_{$i}.{$ext}",
                    'original_name' => "image_{$i}.{$ext}",
                    'mime_type' => $mimeType,
                    'size' => rand(100000, 5000000),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('task_images')->insert($images);
            
            if (($chunk + 1) % 10 == 0 || ($chunk + 1) == $chunks) {
                $this->output("  Progress: " . ($chunk + 1) . "/{$chunks} chunks");
            }
        }
    }

    private function createComments(int $taskCount): void
    {
        $commentCount = (int) ($taskCount * 0.5);
        $chunkSize = 1000;
        $chunks = ceil($commentCount / $chunkSize);
        $now = now();

        $taskIds = DB::table('tasks')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $comments = [];
            $start = $chunk * $chunkSize;
            $end = min($start + $chunkSize, $commentCount);

            for ($i = $start; $i < $end; $i++) {
                $comments[] = [
                    'task_id' => $taskIds[array_rand($taskIds)],
                    'user_id' => $userIds[array_rand($userIds)],
                    'comment' => "Comment " . ($i + 1) . " - " . substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz', 10)), 0, 50),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('task_comments')->insert($comments);
            
            if (($chunk + 1) % 10 == 0 || ($chunk + 1) == $chunks) {
                $this->output("  Progress: " . ($chunk + 1) . "/{$chunks} chunks");
            }
        }
    }

    private function createCommentImages(int $taskCount): void
    {
        $imageCount = (int) ($taskCount * 0.2);
        $chunkSize = 1000;
        $chunks = ceil($imageCount / $chunkSize);
        $now = now();

        $commentIds = DB::table('task_comments')->pluck('id')->toArray();

        if (empty($commentIds)) {
            return;
        }

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $images = [];
            $start = $chunk * $chunkSize;
            $end = min($start + $chunkSize, $imageCount);

            for ($i = $start; $i < $end; $i++) {
                $mimeType = $this->mimeTypes[array_rand($this->mimeTypes)];
                $ext = $this->imageExtensions[array_rand($this->imageExtensions)];

                $images[] = [
                    'comment_id' => $commentIds[array_rand($commentIds)],
                    'image_path' => "comments/image_{$i}.{$ext}",
                    'original_name' => "image_{$i}.{$ext}",
                    'mime_type' => $mimeType,
                    'size' => rand(100000, 5000000),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('comment_images')->insert($images);
            
            if (($chunk + 1) % 10 == 0 || ($chunk + 1) == $chunks) {
                $this->output("  Progress: " . ($chunk + 1) . "/{$chunks} chunks");
            }
        }
    }
}
