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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user1 = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
            ]);

            $user2 = User::create([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
            ]);

            $user3 = User::create([
                'name' => 'Bob Wilson',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
            ]);

            $project1 = Project::create([
                'user_id' => $user1->id,
                'name' => 'Website Redesign',
                'description' => 'Complete redesign of company website',
            ]);

            $project2 = Project::create([
                'user_id' => $user1->id,
                'name' => 'Mobile App Development',
                'description' => 'Build new mobile application',
            ]);

            $project3 = Project::create([
                'user_id' => $user2->id,
                'name' => 'Marketing Campaign',
                'description' => 'Q1 marketing campaign planning',
            ]);

            $tasks = [
                [
                    'project_id' => $project1->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => $user2->id,
                    'title' => 'Design Homepage',
                    'description' => 'Create new homepage design mockup',
                    'priority' => 'high',
                    'due_date' => now()->addDays(5),
                    'status' => 'in_progress',
                ],
                [
                    'project_id' => $project1->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => $user2->id,
                    'title' => 'Implement Responsive Layout',
                    'description' => 'Make website responsive for all devices',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(7),
                    'status' => 'pending',
                ],
                [
                    'project_id' => $project1->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => $user3->id,
                    'title' => 'Fix Navigation Bug',
                    'description' => 'Navigation menu not working on mobile',
                    'priority' => 'high',
                    'due_date' => now()->subDays(2),
                    'status' => 'pending',
                ],
                [
                    'project_id' => $project2->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => $user3->id,
                    'title' => 'Setup API Endpoints',
                    'description' => 'Create REST API endpoints for mobile app',
                    'priority' => 'high',
                    'due_date' => now()->addDays(3),
                    'status' => 'in_progress',
                ],
                [
                    'project_id' => $project2->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => $user2->id,
                    'title' => 'User Authentication',
                    'description' => 'Implement login and registration',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(10),
                    'status' => 'pending',
                ],
                [
                    'project_id' => $project2->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => null,
                    'title' => 'Database Schema Design',
                    'description' => 'Design database structure',
                    'priority' => 'low',
                    'due_date' => now()->subDays(5),
                    'status' => 'completed',
                ],
                [
                    'project_id' => $project3->id,
                    'user_id' => $user2->id,
                    'assigned_user_id' => $user1->id,
                    'title' => 'Create Social Media Posts',
                    'description' => 'Design posts for Instagram and Facebook',
                    'priority' => 'medium',
                    'due_date' => now()->addDays(6),
                    'status' => 'pending',
                ],
                [
                    'project_id' => $project3->id,
                    'user_id' => $user2->id,
                    'assigned_user_id' => $user3->id,
                    'title' => 'Email Campaign Setup',
                    'description' => 'Setup email marketing campaign',
                    'priority' => 'high',
                    'due_date' => now()->addHours(20),
                    'status' => 'pending',
                ],
                [
                    'project_id' => $project3->id,
                    'user_id' => $user2->id,
                    'assigned_user_id' => $user1->id,
                    'title' => 'Analytics Dashboard',
                    'description' => 'Create analytics dashboard for campaign',
                    'priority' => 'low',
                    'due_date' => now()->addHours(11),
                    'status' => 'in_progress',
                ],
                [
                    'project_id' => $project1->id,
                    'user_id' => $user1->id,
                    'assigned_user_id' => $user2->id,
                    'title' => 'Content Review',
                    'description' => 'Review all website content',
                    'priority' => 'medium',
                    'due_date' => now()->subDays(1),
                    'status' => 'pending',
                ],
            ];

            $createdTasks = [];
            foreach ($tasks as $taskData) {
                $taskData['created_at'] = now();
                $taskData['updated_at'] = now();
                $createdTasks[] = Task::create($taskData);
            }

            $task1 = $createdTasks[0];
            $task3 = $createdTasks[2];
            $task4 = $createdTasks[3];

            $taskImages = [
                [
                    'task_id' => $task1->id,
                    'image_path' => 'tasks/sample1.jpg',
                    'original_name' => 'design-mockup.jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => 1024000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $task1->id,
                    'image_path' => 'tasks/sample2.png',
                    'original_name' => 'wireframe.png',
                    'mime_type' => 'image/png',
                    'size' => 2048000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $task4->id,
                    'image_path' => 'tasks/api-diagram.jpg',
                    'original_name' => 'api-diagram.jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => 1536000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            TaskImage::insert($taskImages);

            $comments = [
                [
                    'task_id' => $task1->id,
                    'user_id' => $user2->id,
                    'comment' => 'Working on the design. Will share updates soon.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $task1->id,
                    'user_id' => $user1->id,
                    'comment' => 'Great! Looking forward to seeing the mockup.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $task3->id,
                    'user_id' => $user3->id,
                    'comment' => 'Found the issue. Fixing it now.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $task4->id,
                    'user_id' => $user3->id,
                    'comment' => 'API endpoints are ready for testing.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            $createdComments = [];
            foreach ($comments as $commentData) {
                $createdComments[] = TaskComment::create($commentData);
            }

            $commentImages = [
                [
                    'comment_id' => $createdComments[0]->id,
                    'image_path' => 'comments/design-draft.jpg',
                    'original_name' => 'design-draft.jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => 512000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'comment_id' => $createdComments[2]->id,
                    'image_path' => 'comments/bug-screenshot.png',
                    'original_name' => 'bug-screenshot.png',
                    'mime_type' => 'image/png',
                    'size' => 768000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            CommentImage::insert($commentImages);
        });
    }
}
