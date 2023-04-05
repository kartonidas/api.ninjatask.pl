<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\Exception;
use App\Models\Firm;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRegisterToken;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function getAccount($id = 0)
    {
        return config('testing.accounts')[$id];
    }
    
    protected function userRegister($id = 0)
    {
        $account = $this->getAccount($id);
            
        $response = $this->post('/api/register', ['email' => $account['email']]);
        $status = $response->getContent();
        
        if($status !== "1")
            throw new Exception('Invalid response status');
        
        $user = User::where('email', $account['email'])->where('owner', 1)->first();
        if(!$user)
            throw new Exception('User not exists');
        
        $tokenRow = UserRegisterToken::where('user_id', $user->id)->first();
        if(!$tokenRow)
            throw new Exception('Token not exists');
        
        return $tokenRow->token;
    }
    
    protected function userRegisterWithConfirmation($id = 0)
    {
        $token = $this->userRegister($id);
        
        $response = $this->post('/api/register/confirm/' . $token, $this->getAccount($id)['data']);
        $response->getContent();
    }
    
    protected function getOwnerLoginToken($id = 0)
    {
        $this->userRegisterWithConfirmation($id);
        $data = [
            'email' => $this->getAccount($id)['email'],
            'password' => $this->getAccount($id)['data']['password'],
            'device_name' => 'test'
        ];
        if(User::where("email", $this->getAccount($id)['email'])->active()->count() > 1)
        {
            $user = User::where("email", $this->getAccount($id)['email'])->where('owner', 1)->active()->first();
            $data["firm_id"] = $user->firm_id;
        }
        
        $response = $this->post('/api/login', $data);
        $response->assertStatus(200);
        return $response->getContent();
    }
    
    protected function prepareMultipleUserAccount($params = [])
    {
        for($id = 0; $id < count(config('testing.accounts')); $id++)
        {
            $this->getOwnerLoginToken($id);
            
            $ownerUser = User::where('email', $this->getAccount($id)['email'])->where('owner', 1)->first();
            foreach($this->getAccount($id)['workers'] as $data)
            {
                $user = new User;
                $user->firm_id = $ownerUser->firm_id;
                $user->firstname = $data["firstname"];
                $user->lastname = $data["lastname"];
                $user->email = $data["email"];
                $user->password = Hash::make($data["password"]);
                $user->phone = $data["phone"];
                $user->owner = 0;
                $user->activated = 1;
                $user->user_permission_id = 0;
                $user->superuser = $data["superuser"];
                $user->save();
            }
            
            if(!empty($params["projects"]) && !empty($this->getAccount($id)['projects']))
            {
                foreach($this->getAccount($id)['projects'] as $data)
                {
                    $project = new Project;
                    $project->uuid = $ownerUser->getUuid();
                    $project->name = $data["data"]["name"];
                    $project->description = $data["data"]["description"];
                    $project->saveQuietly();
                    
                    if(!empty($params["tasks"]) && !empty($data["tasks"]))
                    {
                        foreach($data["tasks"] as $data)
                        {
                            $task = new Task;
                            $task->uuid = $ownerUser->getUuid();
                            $task->project_id = $project->id;
                            $task->name = $data["name"];
                            $task->saveQuietly();
                        }
                    }
                }
            }
        }
    }
    
    protected function getAccountUuui($token)
    {
        $response = $this->withToken($token)->getJson('/api/get-firm-id');
        $firmId = $response->getContent();
        
        $firm = Firm::find($firmId);
        return $firm->uuid;
    }
    
    protected function setUserPermission($email, $permissions)
    {
        if(User::where('email', $email)->count() > 1)
            throw new Exception('User with email address ' . $email . ' exists more than once');
        
        $user = User::where('email', $email)->first();
        if(!$user)
            throw new Exception('User with email address ' . $email . ' not exist');
        
        $firm = Firm::find($user->firm_id);
        
        $userPermission = new UserPermission;
        $userPermission->name = "Test";
        $userPermission->uuid = $firm->uuid;
        $userPermission->permissions = $permissions;
        $userPermission->saveQuietly();
        
        $user->superuser = 0;
        $user->user_permission_id = $userPermission->id;
        $user->save();
    }
    
    protected function getProject($token)
    {
        return Project::withoutGlobalScopes()->where('uuid', $this->getAccountUuui($token))->inRandomOrder()->first();
    }
}
