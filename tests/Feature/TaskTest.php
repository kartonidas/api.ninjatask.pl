<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Firm;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignedUser;
use App\Models\TaskTime;
use App\Models\User;

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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $data = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $data['project_id'] = $project->id;
        
        $response = $this->withToken($token)->putJson('/api/v1/task/', $data);
        $response->assertStatus(200);
        
        $uuid = $this->getAccountUuui($token);
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $users = User::where('firm_id', $firm->id)->where('owner', 0)->get();
        $userIds = [];
        foreach($users as $user)
            $userIds[] = $user->id;
        $data['users'] = $userIds;
        
        $response = $this->withToken($token)->putJson('/api/v1/task/', $data);
        $response->assertStatus(200);
        $this->assertDatabaseCount('task_assigned_users', count($userIds));
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $taskData = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $taskData['project_id'] = $project->id;
        
        $requiredFields = ['name'];
        foreach($requiredFields as $field)
        {
            $data = $taskData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/v1/task', $data);
            $response->assertStatus(422);
        }
        
        $uuid = $this->getAccountUuui($token);
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $users = User::where('firm_id', $firm->id)->where('owner', 0)->get();
        
        $data['users'] = [-1];
        $response = $this->withToken($token)->putJson('/api/v1/task/', $data);
        $response->assertStatus(422);
        
        $data['users'] = $users[0]->id;
        $response = $this->withToken($token)->putJson('/api/v1/task/', $data);
        $response->assertStatus(422);
        
        $otherUser = User::withoutGlobalScopes()->where('firm_id', '!=', $firm->id)->inRandomOrder()->first();
        $data['users'] = [$otherUser->id];
        $response = $this->withToken($token)->putJson('/api/v1/task/', $data);
        $response->assertStatus(422);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $taskData = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $taskData['project_id'] = -9;
        
        $response = $this->withToken($token)->putJson('/api/v1/task', $taskData);
        $response->assertStatus(404);
        
        $uuid = $this->getAccountUuui($token);
        $otherUserProject = Project::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $taskData['project_id'] = $otherUserProject->id;
        $response = $this->withToken($token)->putJson('/api/v1/task/', $taskData);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/v1/tasks/' . $project->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $totalTasks = Task::withoutGlobalScopes()->where('project_id', $project->id)->count();
        
        $response = $this->withToken($token)->getJson('/api/v1/tasks/' . $project->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $totalTasks = Task::withoutGlobalScopes()->where('project_id', $project->id)->count();
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . $task->id);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/v1/tasks/' . $project->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $totalTasks = Task::withoutGlobalScopes()->where('project_id', $project->id)->count();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . -9);
        $response->assertStatus(404);
        
        // Try delete otherr users project
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . $otherUserTask->id);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->getJson('/api/v1/tasks/' . $project->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/v1/task/' . $task->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/task/' . -9);
        $response->assertStatus(404);
        
        // Try delete other users task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . $otherUserTask->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/v1/task/' . $task->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);
            
        
        $uuid = $this->getAccountUuui($token);
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $users = User::where('firm_id', $firm->id)->where('owner', 0)->get();
        $userIds = [];
        foreach($users as $user)
            $userIds[] = $user->id;
        $data['users'] = $userIds;
        
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
        $response->assertStatus(200);
        $this->assertDatabaseCount('task_assigned_users', count($userIds));
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/task/' . -9, $data);
        $response->assertStatus(404);
        
        // Try update other users task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . $otherUserTask->id);
        $response->assertStatus(404);
        
        
        $uuid = $this->getAccountUuui($token);
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $users = User::where('firm_id', $firm->id)->where('owner', 0)->get();
        
        $data['users'] = [-2];
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
        $response->assertStatus(422);
        
        $data['users'] = $users[0]->id;
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
        $response->assertStatus(422);
        
        $otherUser = User::withoutGlobalScopes()->where('firm_id', '!=', $firm->id)->inRandomOrder()->first();
        $data['users'] = [$otherUser->id];
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
        $response->assertStatus(422);
    }
    
    // Successfull assgin user to task
    public function test_assign_user_to_task_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        $data = [
            'user_id' => $user->id,
        ];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_assigned_users', 1);
        $this->assertDatabaseHas('task_assigned_users', [
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }
    
    // Error while assgin user to task
    public function test_assign_user_to_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        
        $data = [];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(422);
        
        $data = ['user_id' => $user->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(409);
        
        
        $data = ['user_id' => $user->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/-9/assign', $data);
        $response->assertStatus(404);
        
        $data = ['user_id' => -1];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(404);
        
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $otherUser = User::withoutGlobalScopes()->where('firm_id', '!=', $firm->id)->inRandomOrder()->first();
        
        $data = ['user_id' => $user->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $otherUserTask->id . '/assign', $data);
        $response->assertStatus(404);
        
        $data = ['user_id' => $otherUser->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(404);
        
        $data = ['user_id' => $otherUser->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $otherUserTask->id . '/assign', $data);
        $response->assertStatus(404);
    }
    
    // Successfull deassgin user from task
    public function test_deassign_user_to_task_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $task->id;
        $assign->user_id = $user->id;
        $assign->save();
        
        $data = [
            'user_id' => $user->id,
        ];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_assigned_users', 0);
    }
    
    // Error while deassgin user to task
    public function test_deassign_user_to_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $task->id;
        $assign->user_id = $user->id;
        $assign->save();
        
        $data = [];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(422);
        
        $data = ['user_id' => $user->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(404);
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $task->id;
        $assign->user_id = $user->id;
        $assign->save();
        
        $data = ['user_id' => $user->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/-9/deassign', $data);
        $response->assertStatus(404);
        
        $data = ['user_id' => -1];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(404);
        
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $otherUser = User::withoutGlobalScopes()->where('firm_id', '!=', $firm->id)->inRandomOrder()->first();
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $otherUserTask->id;
        $assign->user_id = $otherUser->id;
        $assign->save();
        
        $data = ['user_id' => $user->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $otherUserTask->id . '/deassign', $data);
        $response->assertStatus(404);
        
        $data = ['user_id' => $otherUser->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(404);
        
        $data = ['user_id' => $otherUser->id];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $otherUserTask->id . '/deassign', $data);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $data = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $data['project_id'] = $project->id;
        
        $response = $this->withToken($token)->putJson('/api/v1/task', $data);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $data = $this->getAccount($accountUserId)['projects'][0]['tasks'][0];
        $data['project_id'] = $project->id;
        
        $response = $this->withToken($token)->putJson('/api/v1/task', $data);
        $response->assertStatus(403);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/v1/tasks/' . $project->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/v1/tasks/' . $project->id);
        $response->assertStatus(403);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . $task->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/task/' . $task->id);
        $response->assertStatus(403);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/v1/task/' . $task->id);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/v1/task/' . $task->id);
        $response->assertStatus(403);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
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
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/task/' . $task->id, $data);
        $response->assertStatus(403);
    }
    
    // Successfull assgin user to task with valid permission
    public function test_permission_assign_user_to_task_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:update");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        $data = [
            'user_id' => $user->id,
        ];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(200);
    }
    
    // Error while assgin user to task with invalid permission
    public function test_permission_assign_user_to_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list,create,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        $data = [
            'user_id' => $user->id,
        ];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/assign', $data);
        $response->assertStatus(403);
    }
    
    // Successfull deassgin user from task with valid permission
    public function test_permission_deassign_user_to_task_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:update");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $task->id;
        $assign->user_id = $user->id;
        $assign->save();
        
        $data = [
            'user_id' => $user->id,
        ];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(200);
    }
    
    // Successfull deassgin user from task with invalid permission
    public function test_permission_deassign_user_to_task_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true, 'tasks' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "task:list,create,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        $uuid = $this->getAccountUuui($token);
        
        $firm = Firm::where('uuid', $uuid)->first();
        $task = Task::where('uuid', $uuid)->inRandomOrder()->first();
        $user = User::where('firm_id', $firm->id)->where('owner', 0)->first();
        
        $assign = new TaskAssignedUser;
        $assign->task_id = $task->id;
        $assign->user_id = $user->id;
        $assign->save();
        
        $data = [
            'user_id' => $user->id,
        ];
        $response = $this->withToken($token)->postJson('/api/v1/task/' . $task->id . '/deassign', $data);
        $response->assertStatus(403);
    }
}