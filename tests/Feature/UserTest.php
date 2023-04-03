<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PasswordResetToken;
use App\Models\UserInvitation;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_login_successfull(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->post('/api/register/confirm/' . $token, $this->userData);
        $status = $response->getContent();
        if($status == '1')
        {
            $data = [
                'email' => $this->testEmail,
                'password' => $this->userData['password'],
                'device_name' => 'test'
            ];
            $response = $this->postJson('/api/login', $data);
            $response->assertStatus(200);
        }
    }
    
    public function test_login_invalid_password(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->postJson('/api/register/confirm/' . $token, $this->userData);
        $status = $response->getContent();
        if($status == '1')
        {
            $data = [
                'email' => $this->testEmail,
                'password' => 'INVALID:PASSWORD',
                'device_name' => 'test'
            ];
            $response = $this->postJson('/api/login', $data);
            $response->assertStatus(422);
        }
    }
    
    public function test_login_inactive_account(): void
    {
        $this->userRegister();
        $data = [
            'email' => $this->testEmail,
            'password' => $this->userData['password'],
            'device_name' => 'test'
        ];
        $response = $this->postJson('/api/login', $data);
        $response->assertStatus(422);
    }
    
    public function test_login_forgot_password_successfull(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $response->assertStatus(200);
    }
    
    public function test_login_forgot_invalid_email(): void
    {
        $data = ['email' => 'xxxx'];
        $response = $this->postJson('/api/forgot-password', $data);
        $response->assertStatus(422);
    }
    
    public function test_login_forgot_not_exist_email(): void
    {
        $data = ['email' => 'invalid@example.com'];
        $response = $this->postJson('/api/forgot-password', $data);
        $response->assertStatus(404);
    }
    
    public function test_login_forgot_inactive_account(): void
    {
        $this->userRegister();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $response->assertStatus(404);
    }
    
    public function test_reset_password_validate_token_successfull(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->testEmail,
                'token' => $tokenRow->token,
            ];
            $response = $this->getJson('/api/reset-password?' . http_build_query($data));
            $response->assertStatus(200);
        }
    }
    
    public function test_reset_password_validate_token_invalid_token(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->testEmail,
                'token' => 'INVALID:TOKEN',
            ];
            $response = $this->getJson('/api/reset-password?' . http_build_query($data));
            $response->assertStatus(422);
        }
    }
    
    public function test_reset_password_validate_token_not_exist_email(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => 'invalid@example.com',
                'token' => $tokenRow->token,
            ];
            $response = $this->getJson('/api/reset-password?' . http_build_query($data));
            $response->assertStatus(422);
        }
    }
    
    public function test_reset_password_validate_token_invalid_params(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $requiredData = ['email', 'token'];
            
            $resetPasswordData = [
                'email' => $this->testEmail,
                'token' => $tokenRow->token,
            ];
            
            foreach($requiredData as $field)
            {
                $data = $resetPasswordData;
                unset($data[$field]);
                
                $response = $this->postJson('/api/reset-password?' .  http_build_query($data));
                $response->assertStatus(422);
            }
        }
    }
    
    public function test_reset_password_successfull(): void
    {
        $this->userRegisterWithConfirmation();
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->testEmail,
                'token' => $tokenRow->token,
                'password' => $this->userData['password'],
                'password_confirmation' => $this->userData['password_confirmation'],
            ];
            $response = $this->postJson('/api/reset-password', $data);
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
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $requiredData = ['email', 'token', 'password', 'password_confirmation'];
            
            $resetPasswordData = [
                'email' => $this->testEmail,
                'token' => $tokenRow->token,
                'password' => $this->userData['password'],
                'password_confirmation' => $this->userData['password_confirmation'],
            ];
            
            foreach($requiredData as $field)
            {
                $data = $resetPasswordData;
                unset($data[$field]);
                
                $response = $this->postJson('/api/reset-password', $data);
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
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => 'invalid@example.com',
                'token' => $tokenRow->token,
                'password' => $this->userData['password'],
                'password_confirmation' => $this->userData['password_confirmation'],
            ];
            
            $response = $this->postJson('/api/reset-password', $data);
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
        $data = ['email' => $this->testEmail];
        $response = $this->postJson('/api/forgot-password', $data);
        $status = $response->getContent();
        if($status == '1')
        {
            $tokenRow = PasswordResetToken::where('email', $this->testEmail)->first();
            if(!$tokenRow)
                throw new Exception('Token not exist');
            
            $data = [
                'email' => $this->testEmail,
                'token' => 'INVALID:TOKEN',
                'password' => $this->userData['password'],
                'password_confirmation' => $this->userData['password_confirmation'],
            ];
            
            $response = $this->postJson('/api/reset-password', $data);
            $response->assertStatus(422);
        }
        else
        {
            throw new Exception('Forgotten password problem');
        }
    }
    
    public function test_user_empty_list(): void
    {
        $token = $this->getOwnerLoginToken();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
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
    
    public function test_user_list_invalid_token(): void
    {
        $token = $this->getOwnerLoginToken();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer INVALID:TOKEN',
        ])->getJson('/api/users');
        
        $response->assertStatus(422);
    }
    
    public function test_create_user_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        foreach($this->workerData as $data)
        {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '. $token,
            ])->putJson('/api/user', $data);
            $response->assertStatus(200);
        }
        
        $this->assertDatabaseCount('users', 4);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 4,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_delete_user_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $userIds = [];
        foreach($this->workerData as $data)
        {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '. $token,
            ])->putJson('/api/user', $data);
            $response->assertStatus(200);
            $userIds[] = $response->getContent();
        }
        
        $this->assertDatabaseCount('users', 4);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 4,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
            
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->deleteJson('/api/user/' . end($userIds));
        $response->assertStatus(200);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 3,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_delete_user_invalid_id(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $userIds = [];
        foreach($this->workerData as $data)
        {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '. $token,
            ])->putJson('/api/user', $data);
            $response->assertStatus(200);
            $userIds[] = $response->getContent();
        }
        
        $this->assertDatabaseCount('users', 4);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 4,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
            
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->deleteJson('/api/user/' . time());
        $response->assertStatus(404);
        
        $this->assertDatabaseCount('users', 4);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 4,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_create_user_invalid_params(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $requiredData = ['firstname', 'lastname', 'email', 'password', 'password_confirmation'];
        $workerData = $this->workerData[0];
        
        foreach($requiredData as $field)
        {
            $data = $workerData;
            unset($data[$field]);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '. $token,
            ])->putJson('/api/user', $data);
            $response->assertStatus(422);
        }
        $this->assertDatabaseCount('users', 1);
        
        // invalid email
        $data = $workerData;
        $data['email'] = 'xxxx';
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(422);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
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
        
        $data = $this->workerData[0];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(200);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(409);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
    
    public function test_user_get_details_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->workerData[0];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . $userId);
        
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
    
    public function test_user_get_details_invalid_id(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->workerData[0];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . time());
        
        $response->assertStatus(404);
    }
    
    public function test_update_user_successfull(): void
    {
        $token = $this->getOwnerLoginToken();
        
        $data = $this->workerData[0];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . $userId);
        
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
        
        $data = $this->workerData[1];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user/' . $userId, $data);
        $response->assertStatus(200);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . $userId);
        
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
        
        $data = $this->workerData[0];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user', $data);
        $response->assertStatus(200);
        $userId = $response->getContent();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . $userId);
        
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
        
        $data = $this->workerData[1];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user/' . time(), $data);
        $response->assertStatus(404);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . $userId);
        
        $data = $this->workerData[0];
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
        foreach($this->workerData as $data)
        {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer '. $token,
            ])->putJson('/api/user', $data);
            $response->assertStatus(200);
            $userIds[] = $response->getContent();
        }
        
        $data = $this->workerData[1];
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->putJson('/api/user/' . $userIds[0], ['firstname' => $this->workerData[1]['firstname'], 'email' => $this->workerData[1]['email']]);
        $response->assertStatus(409);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/user/' . $userIds[0]);
        
        $data = $this->workerData[0];
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
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->postJson('/api/invite', $data);
        $response->assertStatus(200);
        
        $invitationRow = UserInvitation::where('email', 'johndoe@example.com')->first();
        if(!$invitationRow)
            throw new Exception('Token not exist');
        
        // Validate token
        $response = $this->getJson('/api/invite/' . $invitationRow->token);
        $response->assertStatus(200);
        
        // Confirm invitation
        $data = [
            "firstname" => $this->workerData[0]["firstname"],
            "lastname" => $this->workerData[0]["lastname"],
            "password" => $this->workerData[0]["password"],
            "password_confirmation" => $this->workerData[0]["password"],
            "phone" => $this->workerData[0]["phone"],
        ];
        $response = $this->putJson('/api/invite/' . $invitationRow->token, $data);
        $response->assertStatus(200);
        
        // Get user list
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
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
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->postJson('/api/invite', $data);
        $response->assertStatus(409);
        
        // Get user list
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
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
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->postJson('/api/invite', $data);
        $response->assertStatus(200);
        
        $invitationRow = UserInvitation::where('email', 'johndoe@example.com')->first();
        if(!$invitationRow)
            throw new Exception('Token not exist');
        
        // Validate token
        $response = $this->getJson('/api/invite/' . 'INVALID:TOKEN');
        $response->assertStatus(409);
        
        // Confirm invitation
        $data = [
            "firstname" => $this->workerData[0]["firstname"],
            "lastname" => $this->workerData[0]["lastname"],
            "password" => $this->workerData[0]["password"],
            "password_confirmation" => $this->workerData[0]["password"],
            "phone" => $this->workerData[0]["phone"],
        ];
        $response = $this->putJson('/api/invite/' . 'INVALID:TOKEN', $data);
        $response->assertStatus(409);
        
        // Get user list
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
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
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->postJson('/api/invite', $data);
        $response->assertStatus(200);
        
        $invitationRow = UserInvitation::where('email', 'johndoe@example.com')->first();
        if(!$invitationRow)
            throw new Exception('Token not exist');
        
        // Validate token
        $response = $this->getJson('/api/invite/' . $invitationRow->token);
        $response->assertStatus(200);
        
        // Confirm invitation
        $requiredData = ['firstname', 'lastname', 'password', 'password_confirmation'];
        $confirmData = [
            "firstname" => $this->workerData[0]["firstname"],
            "lastname" => $this->workerData[0]["lastname"],
            "password" => $this->workerData[0]["password"],
            "password_confirmation" => $this->workerData[0]["password"],
        ];
        
        foreach($requiredData as $field)
        {
            $data = $confirmData;
            unset($data[$field]);
            $response = $this->putJson('/api/invite/' . $invitationRow->token, $data);
            $response->assertStatus(422);
        }
        
        // Get user list
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->getJson('/api/users');
        
        $response
            ->assertStatus(200)
            ->assertJson([
                'total_rows' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'has_more' => false,
            ]);
    }
}
