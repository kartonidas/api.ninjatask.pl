<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    
    // Error register without email params
    public function test_without_email_params(): void
    {
        $response = $this->postJson('/api/v1/register');
        $response->assertStatus(422);
    }
    
    // Error register with invalid email params
    public function test_invalid_email_params(): void
    {
        $response = $this->postJson('/api/v1/register', ['email' => 'invalid']);
        $response->assertStatus(422);
    }
    
    // Successfull register
    public function test_register_successfull(): void
    {
        $response = $this->postJson('/api/v1/register', ['email' => $this->getAccount(0)['email']]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => $this->getAccount(0)['email'],
        ]);
    }
    
    // Error validate register token (invalid token, empty token)
    public function test_register_token_confirm_invalid_token(): void
    {
        $token = $this->userRegister();
        
        $response = $this->getJson('/api/v1/register/confirm/' . "INVALID:TOKEN");
        $response->assertStatus(422);
        
        $response = $this->getJson('/api/v1/register/confirm/');
        $response->assertStatus(404);
    }
    
    // Successfull validate register token
    public function test_register_token_confirm_successfull(): void
    {
        $token = $this->userRegister();
        
        $response = $this->getJson('/api/v1/register/confirm/' . $token);
        $response->assertStatus(200);
    }
    
    // Error register confirmation (validation errors)
    public function test_register_token_confirmation_invalid_params(): void
    {
        $token = $this->userRegister();
        
        foreach(array_keys($this->getAccount(0)['data']) as $column)
        {
            $data = $this->getAccount(0)['data'];
            unset($data[$column]);
            
            $data = $this->getAccount(0)['data'];
            unset($data["firstname"]);
            $response = $this->postJson('/api/v1/register/confirm/' . $token, $data);
            $response->assertStatus(422);
        }
        
        // too weak password
        $data = $this->getAccount(0)['data'];
        $data['password'] = "123";
        $response = $this->postJson('/api/v1/register/confirm/' . $token, $data);
        $response->assertStatus(422);
    }
    
    // Error register confirmation (invalid token)
    public function test_register_token_confirmation_invalid_token(): void
    {
        $token = $this->userRegister();
        
        $response = $this->postJson('/api/v1/register/confirm/' . "INVALID:TOKEN", $this->getAccount(0)['data']);
        $response->assertStatus(422);
        
        $response = $this->postJson('/api/v1/register/confirm/', $this->getAccount(0)['data']);
        $response->assertStatus(404);
    }
    
    // Successfull register confirmation
    public function test_register_token_confirmation_successfull(): void
    {
        $token = $this->userRegister();
        
        $response = $this->postJson('/api/v1/register/confirm/' . $token, $this->getAccount(0)['data']);
        $response->assertStatus(200);
    }
    
    // Error register confirmation (specified email exist in database)
    public function test_register_email_exists(): void
    {
        $this->test_register_token_confirmation_successfull();
        $response = $this->postJson('/api/v1/register', ['email' => $this->getAccount(0)['email']]);
        $response->assertStatus(422);
    }
    
    // Successfull register confirmation (specified email exist in database but is no owner)
    public function test_register_email_exists_successfull(): void
    {
        $this->test_register_token_confirmation_successfull();
        
        $data = [
            'email' => $this->getAccount(0)['email'],
            'password' => $this->getAccount(0)['data']['password'],
            'device_name' => 'test'
        ];
        $response = $this->post('/api/v1/login', $data);
        $response->assertStatus(200);
        $response = json_decode($response->getContent());
        $loginToken = $response->token;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $loginToken,
        ])->putJson('/api/v1/user', $this->getAccount(0)['workers'][0]);
        $response->assertStatus(200);
        
        $response = $this->postJson('/api/v1/register', ['email' => $this->getAccount(0)['workers'][0]['email']]);
        $response->assertStatus(200);
    }
}
