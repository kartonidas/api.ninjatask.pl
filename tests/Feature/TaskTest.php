<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTime;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    
    // Successfull create new task
    public function test_create_task_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $data = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $data['project_id'] = $project->id;
        
        $response = $this->withToken($token)->putJson('/api/task/', $data);
        $response->assertStatus(200);
    }
    
    // Error while create new task (invalid params)
    public function test_create_task_invalid_params(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $taskData = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $taskData['project_id'] = $project->id;
        
        $requiredFields = ['name'];
        foreach($requiredFields as $field)
        {
            $data = $taskData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/task', $data);
            $response->assertStatus(422);
        }
    }
    
    // Error while create new task (invalid project id)
    public function test_create_task_invalid_project_id(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $taskData = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $taskData['project_id'] = -9;
        
        $response = $this->withToken($token)->putJson('/api/task', $taskData);
        $response->assertStatus(404);
        
        $uuid = $this->getAccountUuui($token);
        $otherUserProject = Project::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $taskData['project_id'] = $otherUserProject->id;
        $response = $this->withToken($token)->putJson('/api/task/', $taskData);
        $response->assertStatus(404);
    }
    
    // Successfull get task empty list
    public function test_get_task_empty_list_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/tasks/' . $project->id);
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
    
    // Successfull get task non-empty list
    public function test_get_task_non_empty_list_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $totalTasks = Task::withoutGlobalScopes()->where('project_id', $project->id)->count();
        
        $response = $this->withToken($token)->getJson('/api/tasks/' . $project->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => $totalTasks,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull delete task
    public function test_delete_task_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $totalTasks = Task::withoutGlobalScopes()->where('project_id', $project->id)->count();
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/tasks/' . $project->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => $totalTasks - 1,
                'total_pages' => $totalTasks - 1 > 0 ? 1 : 0,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Error while delete task (invalid ID)
    public function test_delete_task_invalid_id(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $totalTasks = Task::withoutGlobalScopes()->where('project_id', $project->id)->count();
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . -9);
        $response->assertStatus(404);
        
        // Try delete otherr users project
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/task/' . $otherUserTask->id);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->getJson('/api/tasks/' . $project->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => $totalTasks,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull get task details
    public function test_get_task_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'name' => $task->name,
                'description' => $task->description,
            ]);
    }
    
    // Error while get task details (invalid ID)
    public function test_get_task_invalid_id(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $response = $this->withToken($token)->getJson('/api/task/' . -9);
        $response->assertStatus(404);
        
        // Try delete other users task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/task/' . $otherUserTask->id);
        $response->assertStatus(404);
    }
    
    // Successfull update task
    public function test_update_task_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id, $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);
    }
    
    // Error while update task (invalid ID)
    public function test_update_task_invalid_id(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . -9, $data);
        $response->assertStatus(404);
        
        // Try update other users task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/task/' . $otherUserTask->id);
        $response->assertStatus(404);
    }
    
    // Successfull create task with valid permission
    public function test_permission_create_task_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:create");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $data = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $data['project_id'] = $project->id;
        
        $response = $this->withToken($token)->putJson('/api/task', $data);
        $response->assertStatus(200);
    }
    
    // Error while create task with invalid permission
    public function test_permission_create_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list,update,delete");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $data = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $data['project_id'] = $project->id;
        
        $response = $this->withToken($token)->putJson('/api/task', $data);
        $response->assertStatus(405);
    }
    
    // Successfull get task list with valid permission
    public function test_permission_get_task_list_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/tasks/' . $project->id);
        $response->assertStatus(200);
    }
    
    
    // Error while get task list with invalid permission
    public function test_permission_get_task_list_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:create,update,delete");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/tasks/' . $project->id);
        $response->assertStatus(405);
    }
    
    // Successfull delete task with valid permission
    public function test_permission_delete_task_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:delete");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id);
        $response->assertStatus(200);
    }
    
    // Error while delete task with invalid permission
    public function test_permission_delete_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list,create,update");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id);
        $response->assertStatus(405);
    }
    
    // Successfull get task details with valid permission
    public function test_permission_get_task_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list,create,update,delete");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id);
        $response->assertStatus(200);
    }
    
    // Error while get task details with invalid permission
    public function test_permission_get_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:create,update,delete");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id);
        $response->assertStatus(405);
    }
    
    // Successfull update task with valid permission
    public function test_permission_update_task_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:update");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id, $data);
        $response->assertStatus(200);
    }
    
    // Error while update task with invalid permission
    public function test_permission_update_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list,create,delete");
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id, $data);
        $response->assertStatus(405);
    }
    
    
}