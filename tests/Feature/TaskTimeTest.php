<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTime;

class TaskTimeTest extends TestCase
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
        return $response->token;
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
        return $response->token;
    }
    
    // Successfull start task timer
    public function test_start_time_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::first();
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'status' => TaskTime::ACTIVE
        ]);
    }
    
    // Error while start task timer (timer exist)
    public function test_start_time_timer_exist(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(409);
    }
    
    // Error while start task timer (ivalid task ID)
    public function test_start_time_invalid_task_id(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/-9/time/start');
        $response->assertStatus(404);
        
        // Try start timer other user task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->postJson('/api/task/' . $otherUserTask->id . '/time/start');
        $response->assertStatus(404);
    }
    
    // Successfull stop task timer
    public function test_stop_time_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::first();
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'status' => TaskTime::ACTIVE
        ]);
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/stop');
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::first();
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'status' => TaskTime::FINISHED
        ]);
    }
    
    // Error while stop task timer (timer not exist)
    public function test_stop_time_timer_not_exist(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/stop');
        $response->assertStatus(404);
    }
    
    // Error while start task timer (ivalid task ID)
    public function test_stop_time_invalid_task_id(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/-9/time/stop');
        $response->assertStatus(404);
        
        // Try start timer other user task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->postJson('/api/task/' . $otherUserTask->id . '/time/stop');
        $response->assertStatus(404);
    }
    
    // Successfull pause task timer
    public function test_pause_time_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::first();
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'status' => TaskTime::ACTIVE
        ]);
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/pause');
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::first();
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'status' => TaskTime::PAUSED
        ]);
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::first();
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'status' => TaskTime::ACTIVE
        ]);
    }
    
    // Error while pause task timer (timer not exist)
    public function test_pause_time_timer_not_exist(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/pause');
        $response->assertStatus(404);
    }
    
    // Error while start task timer (ivalid task ID)
    public function test_pause_time_invalid_task_id(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/-9/time/pause');
        $response->assertStatus(404);
        
        // Try start timer other user task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->postJson('/api/task/' . $otherUserTask->id . '/time/pause');
        $response->assertStatus(404);
    }
    
    // Successfull get task time empty list
    public function test_get_time_empty_list_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
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
    
    // Successfull get task time non-empty list
    public function test_get_time_non_empty_list_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/stop');
        $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/stop');
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull task log time
    public function test_log_time_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $times = json_decode($response->getContent());
        $taskTimeRow = TaskTime::find($times->data[0]->id);
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'task_id' => $task->id,
            'status' => TaskTime::FINISHED,
            'started' => $data['started'],
            'finished' => $data['started'] + $data['total'],
            'total' => $data['total'],
            'comment' => $data['comment'],
            'billable' => $data['billable'],
        ]);
    }
    
    // Error while task log time(invalid params)
    public function test_log_time_invalid_params(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $taskTimeData = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $requiredFields = ['started', 'total'];
        foreach($requiredFields as $field)
        {
            $data = $taskTimeData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
            $response->assertStatus(422);
        }
    }
    
    // Error while log time (ivalid task ID)
    public function test_log_time_invalid_task_id(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->putJson('/api/task/-9/time');
        $response->assertStatus(404);
        
        // Try start timer other user task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->putJson('/api/task/' . $otherUserTask->id . '/time');
        $response->assertStatus(404);
    }
    
    // Successfull update task log time
    public function test_update_log_time_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $times = json_decode($response->getContent());
        $taskTimeRow = TaskTime::find($times->data[0]->id);
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'task_id' => $task->id,
            'status' => TaskTime::FINISHED,
            'started' => $data['started'],
            'finished' => $data['started'] + $data['total'],
            'total' => $data['total'],
            'comment' => $data['comment'],
            'billable' => $data['billable'],
        ]);
        
        $data = [
            'started' => time()-290,
            'total' => 250,
            'billable' => 0,
            'comment' => 'Example time comment updated'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time/' . $taskTimeRow->id, $data);
        $response->assertStatus(200);
        
        $taskTimeRow = TaskTime::find($times->data[0]->id);
        $this->assertDatabaseHas('task_times', [
            'id' => $taskTimeRow->id,
            'task_id' => $task->id,
            'status' => TaskTime::FINISHED,
            'started' => $data['started'],
            'finished' => $data['started'] + $data['total'],
            'total' => $data['total'],
            'comment' => $data['comment'],
            'billable' => $data['billable'],
        ]);
    }
    
    // Error while update log time (ivalid task ID)
    public function test_update_log_time_invalid_task_id(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $times = json_decode($response->getContent());
        
        $response = $this->withToken($token)->putJson('/api/task/-9/time/' . $times->data[0]->id);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time/-9');
        $response->assertStatus(404);
        
        //// Try start timer other user task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->putJson('/api/task/' . $otherUserTask->id . '/time/' . $times->data[0]->id);
        $response->assertStatus(404);
    }
    
    // Successfull delete task
    public function test_delete_log_time_successfull(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_times', 1);
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $times = json_decode($response->getContent());
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/time/' . $times->data[0]->id);
        $response->assertStatus(200);
        
        $this->assertDatabaseCount('task_times', 0);
    }
    
    // Error while delete log time (ivalid task ID)
    public function test_delete_log_time_invalid_task_id(): void
    {
        $token = $this->init();
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $times = json_decode($response->getContent());
        
        $response = $this->withToken($token)->deleteJson('/api/task/-9/time/' . $times->data[0]->id);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/time/-9');
        $response->assertStatus(404);
        
        //// Try start timer other user task
        $uuid = $this->getAccountUuui($token);
        $otherUserTask = Task::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/task/' . $otherUserTask->id . '/time/' . $times->data[0]->id);
        $response->assertStatus(404);
    }
    
    // Successfull start time with valid permission
    public function test_permission_start_time_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(200);
    }
    
    // Error start time with invalid permission
    public function test_permission_start_time_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/start');
        $response->assertStatus(405);
    }
    
    // Successfull stop task timer with valid permission
    public function test_permission_stop_time_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/stop');
        $response->assertStatus(200);
    }
    
    // Error stop task timer with invalid permission
    public function test_permission_stop_time_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/stop');
        $response->assertStatus(405);
    }
    
    // Successfull pause task timer with valid permission
    public function test_permission_pause_time_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/pause');
        $response->assertStatus(200);
    }
    
    // Error pause task timer with invalid permission
    public function test_permission_pause_time_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $response = $this->withToken($token)->postJson('/api/task/' . $task->id . '/time/pause');
        $response->assertStatus(405);
    }
    
    // Successfull get task time empty list with valid permission
    public function test_permission_get_time_empty_list_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $response->assertStatus(200);
    }
    
    // Error get task time empty list with invalid permission
    public function test_permission_get_time_empty_list_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/task/' . $task->id . '/times');
        $response->assertStatus(405);
    }
    
    // Successfull delete time with valid permission
    public function test_permission_delete_log_time_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/time/' . $timer->id);
        $response->assertStatus(200);
    }
    
    // Error delete time with invalid permission
    public function test_permission_delete_log_time_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $response = $this->withToken($token)->deleteJson('/api/task/' . $task->id . '/time/' . $timer->id);
        $response->assertStatus(405);
    }
    
    // Successfull task log time with valid permission
    public function test_permission_log_time_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(200);
    }
    
    // Error task log time with invalid permission
    public function test_permission_log_time_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $data = [
            'started' => time(),
            'total' => 120,
            'billable' => 1,
            'comment' => 'Example time comment'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time', $data);
        $response->assertStatus(405);
    }
    
    // Successfull update task log time with valid permission
    public function test_permission_update_log_time_ok(): void
    {
        $token = $this->initPermission('task:list');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->status = TaskTime::FINISHED;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $data = [
            'started' => time()-290,
            'total' => 250,
            'billable' => 0,
            'comment' => 'Example time comment updated'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time/' . $timer->id, $data);
        $response->assertStatus(200);
    }
    
    // Error update task log time with invalid permission
    public function test_permission_update_log_time_error(): void
    {
        $token = $this->initPermission('');
        
        $project = $this->getProject($token);
        $task = Task::withoutGlobalScopes()->where('project_id', $project->id)->inRandomOrder()->first();
        
        $response = $this->withToken($token)->getJson('/api/get-id');
        $userId = $response->getContent();
        
        $timer = new TaskTime;
        $timer->task_id = $task->id;
        $timer->user_id = $userId;
        $timer->started = time();
        $timer->save();
        
        $data = [
            'started' => time()-290,
            'total' => 250,
            'billable' => 0,
            'comment' => 'Example time comment updated'
        ];
        $response = $this->withToken($token)->putJson('/api/task/' . $task->id . '/time/' . $timer->id, $data);
        $response->assertStatus(405);
    }
}
