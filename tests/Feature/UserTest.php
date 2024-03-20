<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Models\UserInvitation;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    // Successfull user login
    public function test_login_successfull(): void
    {
        $token = $this->userRegister();
        
        $response = $this->post('/api/v1/register/confirm/' . $token, $this->getAccount(0)['data']);
        $status = $response->getContent();
        if($status == '1')
        {
            $data = [
                'email' => $this->getAccount(0)['email'],
                'password' => $this->getAccount(0)['data']['password'],
                'device_name' => 'test'
            ];
            $response = $this->postJson('/api/v1/login', $data);
            $response->assertStatus(200);
            $response = json_decode($response->getContent());
            $token = $response->token;
            
            $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
            $response->assertStatus(200);
            
            $user = User::where('email', $this->getAccount(0)['email'])->where('owner', 1)->first();
            $response->assertContent((string)$user->firm_id);
        }
    }
    
    // Error user login (invalid password)
    public function test_login_invalid_password(): void
    {
        $token = $this->userRegister();
        
        $response = $this->postJson('/api/v1/register/confirm/' . $token, $this->getAccount(0)['data']);
        $status = $response->getContent();
        if($status == '1')
        {
            $data = [
                'email' => $this->getAccount(0)['email'],
                'password' => 'INVALID:PASSWORD',
                'device_name' => 'test'
            ];
            $response = $this->postJson('/api/v1/login', $data);
            $response->assertStatus(422);
        }
    }
    
    // Error user login (inactive account)
    public function test_login_inactive_account(): void
    {
        $this->userRegister();
        $data = [
            'email' => $this->getAccount(0)['email'],
            'password' => $this->getAccount(0)['data']['password'],
            'device_name' => 'test'
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response->assertStatus(422);
    }
    
    // Error user login (two or more email address in user table - firm_id required)
    public function test_login_with_firm_id_error(): void
    {
        $this->prepareMultipleUserAccount();
        $data = [
            'email' => $this->getAccount(0)['email'],
            'password' => $this->getAccount(0)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response->assertStatus(422);
    }
    
    // Successfull user login (two or more email address in user table - firm_id required)
    public function test_login_with_firm_id_successfull(): void
    {
        $this->prepareMultipleUserAccount();
        $user = User::where('email', 'arturpatura@gmail.com')->where('owner', 1)->first();
        $data = [
            'email' => 'arturpatura@gmail.com',
            'password' => $this->getAccount(0)['data']['password'],
            'device_name' => 'test',
            'firm_id' => $user->firm_id,
        ];
        $response = $this->postJson('/api/v1/login', $data);
        $response->assertStatus(200);
        $response = json_decode($response->getContent());
        $loginToken = $response->token;
        
        $response = $this->withToken($loginToken)->getJson('/api/v1/get-firm-id');
        $response->assertStatus(200)->assertSeeText($user->firm_id);
    }
    
    // Successfull generate forgot password
    public function test_login_forgot_password_successfull(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $response->assertStatus(200);
    }
    
    // Error generate forgot password (invalid email params)
    public function test_login_forgot_invalid_email(): void
    {
        $data = ['email' => 'xxxx'];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $response->assertStatus(422);
    }
    
    // Error generate forgot password (not exist email)
    public function test_login_forgot_not_exist_email(): void
    {
        $data = ['email' => 'invalid@example.com'];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $response->assertStatus(404);
    }
    
    // Error generate forgot password (inactive account)
    public function test_login_forgot_inactive_account(): void
    {
        $this->userRegister();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $response->assertStatus(404);
    }
    
    public function test_reset_password_validate_token_successfull(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->getAccount(0)['email'],
                'token' => $tokenRow->token,
            ];
            $response = $this->getJson('/api/v1/reset-password?' . http_build_query($data));
            $response->assertStatus(200);
        }
    }
    
    public function test_reset_password_validate_token_invalid_token(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->getAccount(0)['email'],
                'token' => 'INVALID:TOKEN',
            ];
            $response = $this->getJson('/api/v1/reset-password?' . http_build_query($data));
            $response->assertStatus(422);
        }
    }
    
    public function test_reset_password_validate_token_not_exist_email(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => 'invalid@example.com',
                'token' => $tokenRow->token,
            ];
            $response = $this->getJson('/api/v1/reset-password?' . http_build_query($data));
            $response->assertStatus(422);
        }
    }
    
    public function test_reset_password_validate_token_invalid_params(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $requiredData = ['email', 'token'];
            
            $resetPasswordData = [
                'email' => $this->getAccount(0)['email'],
                'token' => $tokenRow->token,
            ];
            
            foreach($requiredData as $field)
            {
                $data = $resetPasswordData;
                unset($data[$field]);
                
                $response = $this->postJson('/api/v1/reset-password?' .  http_build_query($data));
                $response->assertStatus(422);
            }
        }
    }
    
    public function test_reset_password_successfull(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->getAccount(0)['email'],
                'token' => $tokenRow->token,
                'password' => $this->getAccount(0)['data']['password'],
                'password_confirmation' => $this->getAccount(0)['data']['password_confirmation'],
            ];
            $response = $this->postJson('/api/v1/reset-password', $data);
            $response->assertStatus(200);
        }
        else
        {
            throw new Exception('Forgotten password problem');
        }
    }
    
    public function test_reset_password_invalid_params(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $requiredData = ['email', 'token', 'password'];
            
            $resetPasswordData = [
                'email' => $this->getAccount(0)['email'],
                'token' => $tokenRow->token,
                'password' => $this->getAccount(0)['data']['password'],
            ];
            
            foreach($requiredData as $field)
            {
                $data = $resetPasswordData;
                unset($data[$field]);
                
                $response = $this->postJson('/api/v1/reset-password', $data);
                $response->assertStatus(422);
            }
        }
        else
        {
            throw new Exception('Forgotten password problem');
        }
    }
    
    public function test_reset_password_not_exist_email(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => 'invalid@example.com',
                'token' => $tokenRow->token,
                'password' => $this->getAccount(0)['data']['password'],
                'password_confirmation' => $this->getAccount(0)['data']['password_confirmation'],
            ];
            
            $response = $this->postJson('/api/v1/reset-password', $data);
            $response->assertStatus(422);
        }
        else
        {
            throw new Exception('Forgotten password problem');
        }
    }
    
    public function test_reset_password_invalid_token(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->getAccount(0)['email']];
        $response = $this->postJson('/api/v1/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->getAccount(0)['email'])->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->getAccount(0)['email'],
                'token' => 'INVALID:TOKEN',
                'password' => $this->getAccount(0)['data']['password'],
                'password_confirmation' => $this->getAccount(0)['data']['password_confirmation'],
            ];
            
            $response = $this->postJson('/api/v1/reset-password', $data);
            $response->assertStatus(422);
        }
        else
        {
            throw new Exception('Forgotten password problem');
        }
    }
    
    public function test_get_user_empty_list_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
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
    
    public function test_get_user_list_invalid_token(): void
    {
        $token = $this->getOwnerLoginToken();
        $response = $this->withToken('INVALID:TOKEN')->getJson('/api/v1/users');
        
        $response->assertStatus(401);
    }
    
    public function test_create_user_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        foreach($this->getAccount(0)['workers'] as $data)
        {
            $response = $this->withToken($token)->putJson('/api/v1/user', $data);
            $response->assertStatus(200);
        }
        
        $this->assertDatabaseCount('users', count($this->getAccount(0)['workers']) + 1);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => count($this->getAccount(0)['workers']) + 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_create_user_invalid_params(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $requiredData = ['firstname', 'lastname', 'email', 'password'];
        $workerData = $this->getAccount(0)['workers'][0];
        
        foreach($requiredData as $field)
        {
            $data = $workerData;
            unset($data[$field]);
            
            $response = $this->withToken($token)->putJson('/api/v1/user', $data);
            $response->assertStatus(422);
        }
        $this->assertDatabaseCount('users', 1);
        
        // invalid email
        $data = $workerData;
        $data['email'] = 'xxxx';
        
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(422);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_create_user_email_exist(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->getAccount(0)['workers'][0];
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(409);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_delete_user_successfull(): void
    {
        $totalUsers = count($this->getAccount(0)['workers']) + 1;
        $token = $this->getOwnerLoginToken();
        
        $userIds = [];
        foreach($this->getAccount(0)['workers'] as $data)
        {
            $response = $this->withToken($token)->putJson('/api/v1/user', $data);
            $response->assertStatus(200);
            $userIds[] = $response->getContent();
        }
        
        $this->assertDatabaseCount('users', $totalUsers);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => $totalUsers,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
            
        $response = $this->withToken($token)->deleteJson('/api/v1/user/' . end($userIds));
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => $totalUsers - 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_delete_user_invalid_id(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $userIds = [];
        foreach($this->getAccount(0)['workers'] as $data)
        {
            $response = $this->withToken($token)->putJson('/api/v1/user', $data);
            $response->assertStatus(200);
            $userIds[] = $response->getContent();
        }
        
        $this->assertDatabaseCount('users', count($this->getAccount(0)['workers']) + 1);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => count($this->getAccount(0)['workers']) + 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
            
        $response = $this->withToken($token)->deleteJson('/api/v1/user/' . time());
        $response->assertStatus(404);
        
        $this->assertDatabaseCount('users', count($this->getAccount(0)['workers']) + 1);
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => count($this->getAccount(0)['workers']) + 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_get_user_details_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->getAccount(0)['workers'][0];
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $userId);
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'owner' => 0,
                'activated' => 1,
            ]);
    }
    
    public function test_get_user_details_invalid_id(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->getAccount(0)['workers'][0];
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . time());
        
        $response->assertStatus(404);
    }
    
    public function test_update_user_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->getAccount(0)['workers'][0];
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $userId);
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'owner' => 0,
                'activated' => 1,
            ]);
        
        $data = $this->getAccount(0)['workers'][1];
        $response = $this->withToken($token)->putJson('/api/v1/user/' . $userId, $data);
        $response->assertStatus(200);
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $userId);
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'owner' => 0,
                'activated' => 1,
            ]);
    }
    
    public function test_update_user_invalid_id(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->getAccount(0)['workers'][0];
        $response = $this->withToken($token)->putJson('/api/v1/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $userId);
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'owner' => 0,
                'activated' => 1,
            ]);
        
        $data = $this->getAccount(0)['workers'][1];
        $response = $this->withToken($token)->putJson('/api/v1/user/' . time(), $data);
        $response->assertStatus(404);
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $userId);
        
        $data = $this->getAccount(0)['workers'][0];
        $response
            ->assertStatus(200)
            ->assertJson([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'owner' => 0,
                'activated' => 1,
            ]);
    }
    
    public function test_update_user_exist_email(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $userIds = [];
        foreach($this->getAccount(0)['workers'] as $data)
        {
            $response = $this->withToken($token)->putJson('/api/v1/user', $data);
            $response->assertStatus(200);
            $userIds[] = $response->getContent();
        }
        
        $data = $this->getAccount(0)['workers'][1];
        $response = $this->withToken($token)->putJson('/api/v1/user/' . $userIds[0], ['firstname' => $this->getAccount(0)['workers'][1]['firstname'], 'email' => $this->getAccount(0)['workers'][1]['email']]);
        $response->assertStatus(409);
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $userIds[0]);
        
        $data = $this->getAccount(0)['workers'][0];
        $response
            ->assertStatus(200)
            ->assertJson([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'owner' => 0,
                'activated' => 1,
            ]);
    }
    
    public function test_invite_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = ['email' => 'johndoe@example.com'];
        $response = $this->withToken($token)->postJson('/api/v1/invite', $data);
        $response->assertStatus(200);
        
        $invitationRow = UserInvitation::where('email', 'johndoe@example.com')->first();
        if(!$invitationRow)
            throw new Exception('Token not exist');
        
        // Validate token
        $response = $this->getJson('/api/v1/invite/' . $invitationRow->token);
        $response->assertStatus(200);
        
        // Confirm invitation
        $data = [
            "firstname" => $this->getAccount(0)['workers'][0]["firstname"],
            "lastname" => $this->getAccount(0)['workers'][0]["lastname"],
            "password" => $this->getAccount(0)['workers'][0]["password"],
            "password_confirmation" => $this->getAccount(0)['workers'][0]["password"],
            "phone" => $this->getAccount(0)['workers'][0]["phone"],
        ];
        $response = $this->putJson('/api/v1/invite/' . $invitationRow->token, $data);
        $response->assertStatus(200);
        
        // Get user list
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_invite_email_exist(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = ['email' => 'arturpatura@gmail.com'];
        $response = $this->withToken($token)->postJson('/api/v1/invite', $data);
        $response->assertStatus(409);
        
        // Get user list
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_invite_invalid_token(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = ['email' => 'johndoe@example.com'];
        $response = $this->withToken($token)->postJson('/api/v1/invite', $data);
        $response->assertStatus(200);
        
        $invitationRow = UserInvitation::where('email', 'johndoe@example.com')->first();
        if(!$invitationRow)
            throw new Exception('Token not exist');
        
        // Validate token
        $response = $this->getJson('/api/v1/invite/' . 'INVALID:TOKEN');
        $response->assertStatus(409);
        
        // Confirm invitation
        $data = [
            "firstname" => $this->getAccount(0)['workers'][0]["firstname"],
            "lastname" => $this->getAccount(0)['workers'][0]["lastname"],
            "password" => $this->getAccount(0)['workers'][0]["password"],
            "password_confirmation" => $this->getAccount(0)['workers'][0]["password"],
            "phone" => $this->getAccount(0)['workers'][0]["phone"],
        ];
        $response = $this->putJson('/api/v1/invite/' . 'INVALID:TOKEN', $data);
        $response->assertStatus(409);
        
        // Get user list
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_invite_invalid_params(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = ['email' => 'johndoe@example.com'];
        $response = $this->withToken($token)->postJson('/api/v1/invite', $data);
        $response->assertStatus(200);
        
        $invitationRow = UserInvitation::where('email', 'johndoe@example.com')->first();
        if(!$invitationRow)
            throw new Exception('Token not exist');
        
        // Validate token
        $response = $this->getJson('/api/v1/invite/' . $invitationRow->token);
        $response->assertStatus(200);
        
        // Confirm invitation
        $requiredData = ['firstname', 'lastname', 'password'];
        $confirmData = [
            "firstname" => $this->getAccount(0)['workers'][0]["firstname"],
            "lastname" => $this->getAccount(0)['workers'][0]["lastname"],
            "password" => $this->getAccount(0)['workers'][0]["password"],
        ];
        
        foreach($requiredData as $field)
        {
            $data = $confirmData;
            unset($data[$field]);
            $response = $this->putJson('/api/v1/invite/' . $invitationRow->token, $data);
            $response->assertStatus(422);
        }
        
        // Get user list
        $response = $this->withToken($token)->getJson('/api/v1/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_permission_get_user_list_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        $response->assertStatus(200);
    }
    
    public function test_permission_get_user_list_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/users');
        $response->assertStatus(403);
    }
    
    public function test_permission_create_user_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:create");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->putJson('/api/v1/user', $this->getAccount(0)['workers'][0]);
        $response->assertStatus(200);
    }
    
    public function test_permission_create_user_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->putJson('/api/v1/user', $this->getAccount(0)['workers'][0]);
        $response->assertStatus(403);
    }
    
    public function test_permission_update_user_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:update");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = $firmId;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->putJson('/api/v1/user/' . $user->id);
        $response->assertStatus(200);
    }
    
    public function test_permission_update_user_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list,create,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = $firmId;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->putJson('/api/v1/user/' . $user->id);
        $response->assertStatus(403);
    }
    
    public function test_permission_update_user_other_account_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list,create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $user = new User;
        $user->firm_id = 99;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->putJson('/api/v1/user/' . $user->id);
        $response->assertStatus(404);
    }
    
    public function test_permission_delete_user_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = $firmId;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/user/' . $user->id);
        $response->assertStatus(200);
    }
    
    public function test_permission_delete_user_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list,create,update");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = $firmId;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/user/' . $user->id);
        $response->assertStatus(403);
    }
    
    public function test_permission_delete_user_other_account_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list,create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = 99;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->deleteJson('/api/v1/user/' . $user->id);
        $response->assertStatus(404);
    }
    
    public function test_permission_get_user_details_ok(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = $firmId;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $user->id);
        
        $response->assertStatus(200);
    }
    
    public function test_permission_get_user_details_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = $firmId;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $user->id);
        
        $response->assertStatus(403);
    }
    
    public function test_permission_get_user_details_other_account_error(): void
    {
        $accountUserId = 2;
        $this->prepareMultipleUserAccount(['projects' => true]);
        $data = [
            'email' => $this->getAccount($accountUserId)['workers'][1]['email'],
            'password' => $this->getAccount($accountUserId)['workers'][1]['password'],
            'device_name' => 'test',
        ];
        $this->setUserPermission($data['email'], "user:list,create,update,delete");
        $response = $this->postJson('/api/v1/login', $data);
        $response = json_decode($response->getContent());
        $token = $response->token;
        
        $response = $this->withToken($token)->getJson('/api/v1/get-firm-id');
        $firmId = $response->getContent();
        
        $user = new User;
        $user->firm_id = 99;
        $user->email = 'example@wp.pl';
        $user->password = 'example';
        $user->save();
        
        $response = $this->withToken($token)->getJson('/api/v1/user/' . $user->id);
        
        $response->assertStatus(404);
    }
}
