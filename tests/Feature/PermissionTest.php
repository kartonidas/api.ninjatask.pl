<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Firm;
use App\Models\UserPermission;

class PermissionTest extends TestCase
{
    use RefreshDatabase;
    
    private function init()
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount();
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
        $this->prepareMultipleUserAccount();
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
    
    // Successfull create new permission
    public function test_create_permission_successfull(): void
    {
        $token = $this->init();
        
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list',
            'is_default' => true
        ];
        $response = $this->withToken($token)->putJson('/api/permission/', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_permissions', [
            'id' => $response->getContent(),
            'permissions' => $data['permissions'],
            'is_default' => 1,
        ]);
        
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list',
            'is_default' => false
        ];
        $response = $this->withToken($token)->putJson('/api/permission/', $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_permissions', [
            'id' => $response->getContent(),
            'permissions' => $data['permissions'],
            'is_default' => 0,
        ]);
    }
    
    // Successfull create new permission
    public function test_create_permission_invalid_params(): void
    {
        $token = $this->init();
        
        $permissionData = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list'
        ];
        
        $requiredFields = ['name'];
        foreach($requiredFields as $field)
        {
            $data = $permissionData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/permission', $data);
            $response->assertStatus(422);
        }
        
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'taskx'
        ];
        $response = $this->withToken($token)->putJson('/api/permission', $data);
        $response->assertStatus(422);
        
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task'
        ];
        $response = $this->withToken($token)->putJson('/api/permission', $data);
        $response->assertStatus(422);
    }
    
    // Successfull get permission list
    public function test_get_permission_non_empty_list_successfull(): void
    {
        $token = $this->init();
        $total = UserPermission::where('uuid', $this->getAccountUuui($token))->count();
        
        $response = $this->withToken($token)->getJson('/api/permissions');
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => $total,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
                'data' => []
            ]);
    }
    
    // Successfull delete permission
    public function test_delete_permission_successfull(): void
    {
        $token = $this->init();
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id);
        $response->assertStatus(200);
    }
    
    // Error while delete permission
    public function test_delete_permission_error(): void
    {
        $token = $this->init();
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 1;
        $permission->save();
        
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id);
        $response->assertStatus(422);
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->deleteJson('/api/permission/-9');
        $response->assertStatus(404);
        
        // Try delete other users permission
        $uuid = $this->getAccountUuui($token);
        $otherUserPermission = UserPermission::withoutGlobalScopes()->where('uuid', '!=', $this->getAccountUuui($token))->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $otherUserPermission->id);
        $response->assertStatus(404);
    }
    
    // Successfull get permission details
    public function test_get_permission_successfull(): void
    {
        $token = $this->init();
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = 'task:create,delete';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->getJson('/api/permission/' . $permission->id);
        $response
            ->assertStatus(200)
            ->assertJson([
                'name' => $permission->name,
                'permissions' => ['task' => ['create', 'delete']],
            ]);
    }
    
    // Error while get permission details (invalid ID)
    public function test_get_permission_invalid_id(): void
    {
        $token = $this->init();
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->getJson('/api/permission/' . -9);
        $response->assertStatus(404);
        
        // Try get other users permission
        $uuid = $this->getAccountUuui($token);
        $otherUserPermission = UserPermission::withoutGlobalScopes()->where('uuid', '!=', $this->getAccountUuui($token))->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $otherUserPermission->id);
        $response->assertStatus(404);
    }
    
    // Successfull update permission
    public function test_update_permission_successfull(): void
    {
        $token = $this->init();
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'name' => 'Name updated',
            'permissions' => 'task:create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id, $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permission->id,
            'permissions' => 'task:create',
        ]);
    }
    
    // Successfull validate default permission flag
    public function test_validate_default_permission_flag_successfull(): void
    {
        $token = $this->init();
        $totalDefault = UserPermission::where('uuid', $this->getAccountUuui($token))->where('is_default', 1)->count();
        $this->assertTrue($totalDefault == 1);
        
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list',
            'is_default' => true
        ];
        $response = $this->withToken($token)->putJson('/api/permission/', $data);
        $permissionId = $response->getContent();
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permissionId,
            'is_default' => 1,
        ]);
        $totalDefault = UserPermission::where('uuid', $this->getAccountUuui($token))->where('is_default', 1)->count();
        $this->assertTrue($totalDefault == 1);
        
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/', $data);
        $permissionId = $response->getContent();
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permissionId,
            'is_default' => 0,
        ]);
        $totalDefault = UserPermission::where('uuid', $this->getAccountUuui($token))->where('is_default', 1)->count();
        $this->assertTrue($totalDefault == 1);
        
        $data = [
            'is_default' => true,
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permissionId, $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permissionId,
            'is_default' => 1,
        ]);
        $totalDefault = UserPermission::where('uuid', $this->getAccountUuui($token))->where('is_default', 1)->count();
        $this->assertTrue($totalDefault == 1);
    }
    
    // Error while update permission (invalid ID, invalid params)
    public function test_update_permission_error(): void
    {
        $token = $this->init();
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . -9, $data);
        $response->assertStatus(404);
        
        $data = [
            'permissions' => 'tasks:create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id, $data);
        $response->assertStatus(422);
        
        // Try update other users permission
        $uuid = $this->getAccountUuui($token);
        $otherUserPermission = UserPermission::withoutGlobalScopes()->where('uuid', '!=', $this->getAccountUuui($token))->inRandomOrder()->first();
        $response = $this->withToken($token)->putJson('/api/permission/' . $otherUserPermission->id, $data);
        $response->assertStatus(404);
    }
    
    // Successfull add permission
    public function test_add_permission_successfull(): void
    {
        $token = $this->init();
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permission->id,
            'permissions' => 'task:create',
        ]);
        
        $data = [
            'object' => 'user',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permission->id,
            'permissions' => 'task:create;user:list,create,update,delete',
        ]);
    }
    
    // Error while add permission
    public function test_add_permission_error(): void
    {
        $token = $this->init();
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/-9/add', $data);
        $response->assertStatus(404);
        
        $data = [];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(422);
        
        $data = [
            'object' => 'invalid',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(422);
        
        $data = [
            'object' => 'invalid',
            'action' => 'invalid',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(422);
        
        // Try add other users permission
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $uuid = $this->getAccountUuui($token);
        $otherUserPermission = UserPermission::withoutGlobalScopes()->where('uuid', '!=', $this->getAccountUuui($token))->inRandomOrder()->first();
        $response = $this->withToken($token)->putJson('/api/permission/' . $otherUserPermission->id . '/add', $data);
        $response->assertStatus(404);
    }
    
    // Successfull remove permission
    public function test_remove_permission_successfull(): void
    {
        $token = $this->init();
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = 'task:create;user:list,create,update;permission:list,update';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'user'
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permission->id,
            'permissions' => 'task:create;permission:list,update',
        ]);
        
        $data = [
            'object' => 'permission',
            'action' => 'list'
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(200);
        
        $this->assertDatabaseHas('user_permissions', [
            'id' => $permission->id,
            'permissions' => 'task:create;permission:update',
        ]);
    }
    
    // Error while remove permission
    public function test_remove_permission_error(): void
    {
        $token = $this->init();
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = 'task:create;user:list,create,update;permission:list,update';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/-9/del', $data);
        $response->assertStatus(404);
        
        $data = [];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(422);
        
        $data = [
            'object' => 'invalid',
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(422);
        
        $data = [
            'object' => 'invalid',
            'action' => 'invalid',
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(422);
        
        // Try remove other users permission
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $uuid = $this->getAccountUuui($token);
        $otherUserPermission = UserPermission::withoutGlobalScopes()->where('uuid', '!=', $this->getAccountUuui($token))->inRandomOrder()->first();
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $otherUserPermission->id . '/del', $data);
        $response->assertStatus(404);
    }
    
    // Successfull create new permission with valid permission
    public function test_permission_create_permission_ok(): void
    {
        $token = $this->initPermission('permission:create');
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/', $data);
        $response->assertStatus(200);
    }
    
    // Error while create new permission with invalid permission
    public function test_permission_create_permission_error(): void
    {
        $token = $this->initPermission('permission:list,update,delete');
        $data = [
            'name' => 'Example Permission',
            'permissions' => 'task:list,delete;user:list',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/', $data);
        $response->assertStatus(405);
    }
    
    // Successfull get permission list with valid permission
    public function test_permission_get_permission_non_empty_list_ok(): void
    {
        $token = $this->initPermission('permission:list');
        $response = $this->withToken($token)->getJson('/api/permissions');
        $response->assertStatus(200);
    }
    
    // Error while get permission list with invalid permission
    public function test_permission_get_permission_non_empty_list_error(): void
    {
        $token = $this->initPermission('permission:create,update,delete');
        $response = $this->withToken($token)->getJson('/api/permissions');
        $response->assertStatus(405);
    }
    
    // Successfull delete permission with valid permission
    public function test_permission_delete_permission_ok(): void
    {
        $token = $this->initPermission('permission:delete');
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id);
        $response->assertStatus(200);
    }
    
    // Error while delete permission invalid permission
    public function test_permission_delete_permission_error(): void
    {
        $token = $this->initPermission('permission:list,create,update');
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 1;
        $permission->save();
        
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id);
        $response->assertStatus(405);
    }
    
    // Successfull get permission details with valid permission
    public function test_permission_get_permission_ok(): void
    {
        $token = $this->initPermission('permission:list');
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = 'task:create,delete';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->getJson('/api/permission/' . $permission->id);
        $response->assertStatus(200);
    }
    
    // Error while get permission details with invalid permission
    public function test_permission_get_permission_invalid_id(): void
    {
        $token = $this->initPermission('permission:create,update,delete');
        
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $response = $this->withToken($token)->getJson('/api/permission/' . $permission->id);
        $response->assertStatus(405);
    }
    
    // Successfull update permission with valid permission
    public function test_permission_update_permission_ok(): void
    {
        $token = $this->initPermission('permission:update');
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'name' => 'Name updated',
            'permissions' => 'task:create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id, $data);
        $response->assertStatus(200);
    }
    
    // Error while update permission with invalid permission
    public function test_permission_update_permission_invalid_id(): void
    {
        $token = $this->initPermission('permission:list,create,delete');
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'name' => 'Name updated',
            'description' => 'Description updated',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id, $data);
        $response->assertStatus(405);
    }
    
    // Successfull add permission with valid permission
    public function test_permission_add_permission_ok(): void
    {
        $token = $this->initPermission('permission:update');
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(200);
    }
    
    // Successfull add permission with invalid permission
    public function test_permission_add_permission_error(): void
    {
        $token = $this->initPermission('permission:list,create,delete');
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = '';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'task',
            'action' => 'create',
        ];
        $response = $this->withToken($token)->putJson('/api/permission/' . $permission->id . '/add', $data);
        $response->assertStatus(405);
    }
    
    // Successfull remove permission with valid permission
    public function test_permission_remove_permission_ok(): void
    {
        $token = $this->initPermission('permission:update');
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = 'task:create;user:list,create,update;permission:list,update';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'user'
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(200);
    }
    
    // Successfull remove permission with invalid permission
    public function test_permission_remove_permission_error(): void
    {
        $token = $this->initPermission('permission:list,create,delete');
        $permission = new UserPermission;
        $permission->name = 'Exaple permission';
        $permission->uuid = $this->getAccountUuui($token);
        $permission->permissions = 'task:create;user:list,create,update;permission:list,update';
        $permission->is_default = 0;
        $permission->save();
        
        $data = [
            'object' => 'user'
        ];
        $response = $this->withToken($token)->deleteJson('/api/permission/' . $permission->id . '/del', $data);
        $response->assertStatus(405);
    }
}
