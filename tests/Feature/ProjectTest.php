<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Project;

class ProjectTest extends TestCase
{
    use RefreshDatabase;
    
    // Successfull create new project
    public function test_create_project_successfull(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount();
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->putJson('/api/v1/project', $this->getAccount($accountUserId)['projects'][0]['data']);
        $response->assertStatus(200);
    }
    
    // Error while create new project (invalid params)
    public function test_create_project_invalid_params(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount();
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $projectData = $this->getAccount($accountUserId)['projects'][0]['data'];
        
        $requiredFields = ['name'];
        foreach($requiredFields as $field)
        {
            $data = $projectData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/v1/project', $data);
            $response->assertStatus(422);
        }
    }
    
    // Successfull get project empty list
    public function test_project_empty_list(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount();
        $data = [
            'email' => $this->getAccount($accountUserId)['email'],
            'password' => $this->getAccount($accountUserId)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
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
    
    // Successfull get project non-empty list
    public function test_project_list(): void
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
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => count($this->getAccount($accountUserId)['projects']),
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull delete project
    public function test_delete_project_successfull(): void
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
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $projects = json_decode($response->getContent());
        
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . $projects->data[0]->id);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => count($this->getAccount($accountUserId)['projects']) - 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Error while delete project (invalid ID)
    public function test_delete_project_invalid_id(): void
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
        
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . -9);
        $response->assertStatus(404);
        
        // Try delete otherr users project
        $uuid = $this->getAccountUuui($token);
        $otherUserProject = Project::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . $otherUserProject->id);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => count($this->getAccount($accountUserId)['projects']),
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull get project details
    public function test_project_get_details_successfull(): void
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
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $projects = json_decode($response->getContent());
        
        $projectDB = Project::find($projects->data[0]->id);
        $response = $this->withToken($token)->getJson('/api/v1/project/' . $projects->data[0]->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'name' => $projectDB->name,
                'location' => $projectDB->location,
                'description' => $projectDB->description,
                'owner' => $projectDB->owner,
            ]);
    }
    
    // Error while get project details (invalid ID)
    public function test_project_get_details_invalid_id(): void
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
        
        $response = $this->withToken($token)->getJson('/api/v1/project/' . -9);
        $response->assertStatus(404);
        
        // Try delete otherr users project
        $uuid = $this->getAccountUuui($token);
        $otherUserProject = Project::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . $otherUserProject->id);
        $response->assertStatus(404);
    }
    
    // Successfull update project
    public function test_update_project_successfull(): void
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
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $projects = json_decode($response->getContent());
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
            'location' => 'Location updated',
            'owner' => 'Owner updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/project/' . $projects->data[0]->id, $data);
        $response->assertStatus(200);
        
        $projectDB = Project::find($projects->data[0]->id);
        $response = $this->withToken($token)->getJson('/api/v1/project/' . $projects->data[0]->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'name' => $data['name'],
                'location' => $data['location'],
                'description' => $data['description'],
                'owner' => $data['owner'],
            ]);
    }
    
    // Error while update project (invalid ID)
    public function test_update_project_invalid_id(): void
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
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
            'location' => 'Location updated',
            'owner' => 'Owner updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/project/' . -9, $data);
        $response->assertStatus(404);
        
        // Try delete otherr users project
        $uuid = $this->getAccountUuui($token);
        $otherUserProject = Project::withoutGlobalScopes()->where('uuid', '!=', $uuid)->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . $otherUserProject->id);
        $response->assertStatus(404);
    }
    
    // Error while create project with invalid permission
    public function test_create_project_permission_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:list,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->putJson('/api/v1/project', $this->getAccount($accountUserId)['projects'][0]['data']);
        $response->assertStatus(403);
    }
    
    // Successfull create project with valid permission
    public function test_create_project_permission_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:create");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->putJson('/api/v1/project', $this->getAccount($accountUserId)['projects'][0]['data']);
        $response->assertStatus(200);
    }
    
    // Error while get project list with invalid permission
    public function test_list_project_permission_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $response->assertStatus(403);
    }
    
    // Successfull get project list with valid permission
    public function test_list_project_permission_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:list");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/projects');
        $response->assertStatus(200);
    }
    
    // Error while delete project with invalid permission
    public function test_delete_project_permission_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:list,create,update");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . $project->id);
        $response->assertStatus(403);
    }
    
    // Successfull delete project with valid permission
    public function test_delete_project_permission_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->deleteJson('/api/v1/project/' . $project->id);
        $response->assertStatus(200);
    }
    
    // Error while get project details with invalid permission
    public function test_project_get_details_permission_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/v1/project/' . $project->id);
        $response->assertStatus(403);
    }
    
    // Successfull get project details with valid permission
    public function test_project_get_details_permission_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:list,create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $response = $this->withToken($token)->getJson('/api/v1/project/' . $project->id);
        $response->assertStatus(200);
    }
    
    // Error while update project with invalid permission
    public function test_update_project_permission_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:list,create,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
            'location' => 'Location updated',
            'owner' => 'Owner updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/project/' . $project->id, $data);
        $response->assertStatus(403);
    }
    
    // Successfull update project with valid permission
    public function test_update_project_permission_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "project:update");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $project = $this->getProject($token);
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
            'location' => 'Location updated',
            'owner' => 'Owner updated',
        ];
        $response = $this->withToken($token)->putJson('/api/v1/project/' . $project->id, $data);
        $response->assertStatus(200);
    }
}