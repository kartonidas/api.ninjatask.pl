<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Task;
use App\Models\TaskComment;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;
    
    private function init()
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        return [$task, $token];
    }
    
    private function initPermission($permission = '')
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], $permission);
        $response = $this->postJson('/api/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        return [$task, $token];
    }
    
    // Successfull create new comment
    public function test_create_comment_successfull(): void
    {
        list($task, $token) = $this->init();
        
        $data = [
            'comment' => 'Example comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_comments', 1);
        $this->assertDatabaseHas('task_comments', [
            'comment' => $data['comment']
        ]);
    }
    
    // Error while create new comment (invalid params)
    public function test_create_comment_invalid_params(): void
    {
        list($task, $token) = $this->init();
        
        $commentData = [
            'comment' => 'Example comment'
        ];
        $requiredFields = ['comment'];
        foreach($requiredFields as $field)
        {
            $data = $commentData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment', $data);
            $response->assertStatus(422);
        }
    }
    
    // Error while create new comment (invalid task id)
    public function test_create_comment_invalid_task_id(): void
    {
        list($task, $token) = $this->init();
        
        $data = [
            'comment' => 'Example comment'
        ];
        $response = $this->putJson('/api/task/' . $task->id . '/comment', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_comments', 1);
        $this->assertDatabaseHas('task_comments', [
            'comment' => $data['comment']
        ]);
        
        $response = $this->withToken($token)->putJson('/api/task/-9/comment', $data);
        $response->assertStatus(404);
        
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->putJson('/api/task/' . $otherUserTask->id . 'comment', $data);
        $response->assertStatus(404);
    }
    
    // Successfull get comments empty list
    public function test_get_comment_empty_list_successfull(): void
    {
        list($task, $token) = $this->init();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comments');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 0,
                'total_pages' => 0,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull get comments non-empty list
    public function test_get_comment_non_empty_list_successfull(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comments');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull delete comment
    public function test_delete_comment_successfull(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $this->assertDatabaseCount('task_comments', 1);
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/comment/' . $comment->id);
        $response->assertStatus(200);
        $this->assertDatabaseCount('task_comments', 0);
    }
    
    // Error while delete comment (invalid ID)
    public function test_delete_comment_invalid_id(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $this->assertDatabaseCount('task_comments', 1);
        $response = $this->withToken($token)->deleteJson('/api/task/-9/comment/' . $comment->id);
        $response->assertStatus(404);
        $this->assertDatabaseCount('task_comments', 1);
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/comment/-9');
        $response->assertStatus(404);
        $this->assertDatabaseCount('task_comments', 1);
        
        // Try delete other users task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/task/-9/comment/' . $comment->id);
        $response->assertStatus(404);
        $this->assertDatabaseCount('task_comments', 1);
    }
    
    // Successfull get comment details
    public function test_get_comment_successfull(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comment/' . $comment->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'comment' => 'Example comment',
            ]);
    }
    
    // Error while get comment details (invalid ID)
    public function test_get_comment_invalid_id(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comment/-9');
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->getJson('/api/task/-9/comment/' . $comment->id);
        $response->assertStatus(404);
        
        // Try get other users task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->getJson('/api/task/' . $otherUserTask->id . '/comment/' . $comment->id);
        $response->assertStatus(404);
    }
    
    
    // Successfull update comment
    public function test_update_comment_successfull(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $data = [
            'comment' => 'Comment updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment/' . $comment->id, $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'comment' => $data['comment']
        ]);
    }
    
    // Error while update comment (invalid ID)
    public function test_update_comment_invalid_id(): void
    {
        list($task, $token) = $this->init();
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $data = [
            'comment' => 'Comment updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/-9/comment/' . $comment->id, $data);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment/-9', $data);
        $response->assertStatus(404);
        
        // Try delete otherr users project
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->putJson('/api/task/' . $otherUserTask->id . '/comment/' . $comment->id);
        $response->assertStatus(404);
    }
    
    // Successfull create comment with valid permission
    public function test_permission_create_comment_ok(): void
    {
        list($task, $token) = $this->initPermission('task:list');
        
        $data = [
            'comment' => 'Example comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_comments', 1);
        $this->assertDatabaseHas('task_comments', [
            'comment' => $data['comment']
        ]);
    }
    
    // Error while create comment with invalid permission
    public function test_permission_create_comment_error(): void
    {
        list($task, $token) = $this->initPermission('');
        
        $data = [
            'comment' => 'Example comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment', $data);
        $response->assertStatus(405);
    }
    
    // Successfull get comment list with valid permission
    public function test_permission_get_comment_list_ok(): void
    {
        list($task, $token) = $this->initPermission('task:list');
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comments');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 0,
                'total_pages' => 0,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Error while get comment list with invalid permission
    public function test_permission_get_comment_list_error(): void
    {
        list($task, $token) = $this->initPermission('');
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comments');
        $response->assertStatus(405);
    }
    
    // Successfull delete comment with valid permission
    public function test_permission_delete_comment_ok(): void
    {
        list($task, $token) = $this->initPermission('task:list');
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $this->assertDatabaseCount('task_comments', 1);
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/comment/' . $comment->id);
        $response->assertStatus(200);
        $this->assertDatabaseCount('task_comments', 0);
    }
    
    // Error while delete comment with invalid permission
    public function test_permission_delete_comment_error(): void
    {
        list($task, $token) = $this->initPermission('');
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $this->assertDatabaseCount('task_comments', 1);
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/comment/' . $comment->id);
        $response->assertStatus(405);
        $this->assertDatabaseCount('task_comments', 1);
    }
    
    // Successfull get comment with valid permission
    public function test_permission_get_comment_ok(): void
    {
        list($task, $token) = $this->initPermission('task:list');
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comment/' . $comment->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'comment' => 'Example comment',
            ]);
    }
    
    // Error while get comment with invalid permission
    public function test_permission_get_comment_error(): void
    {
        list($task, $token) = $this->initPermission('');
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/comment/' . $comment->id);
        $response->assertStatus(405);
    }
    
    // Successfull update comment with valid permission
    public function test_premission_update_comment_ok(): void
    {
        list($task, $token) = $this->initPermission('task:list');
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $data = [
            'comment' => 'Comment updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment/' . $comment->id, $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'comment' => $data['comment']
        ]);
    }
    
    // Error while update comment with invalid permission
    public function test_premission_update_comment_error(): void
    {
        list($task, $token) = $this->initPermission('');
        
        $comment = new TaskComment;
        $comment->user_id = 0;
        $comment->task_id = $task->id;
        $comment->comment = 'Example comment';
        $comment->save();
        
        $data = [
            'comment' => 'Comment updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/comment/' . $comment->id, $data);
        $response->assertStatus(405);
        
        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->id,
            'comment' => $comment->comment
        ]);
    }
}
