<?php

namespace Tests\Feature;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_without_email_params(): void
    {
        $response = $this->postJson('/api/register');
        $response->assertStatus(422);
    }
    
    public function test_invalid_email_params(): void
    {
        $response = $this->postJson('/api/register', ['email' => 'invalid']);
        $response->assertStatus(422);
    }
    
    public function test_register_successfull(): void
    {
        $response = $this->postJson('/api/register', ['email' => $this->testEmail]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => $this->testEmail,
        ]);
    }
    
    public function test_register_token_confirm_successfull(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->withHeaders(['Accept' => 'application/json'])->get('/api/register/confirm/' . $token);
        $response->assertStatus(200);
    }
    
    public function test_register_token_confirm_invalid_token(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->getJson('/api/register/confirm/' . "INVALID:TOKEN");
        $response->assertStatus(422);
    }
    
    public function test_register_token_confirmation_successfull(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->postJson('/api/register/confirm/' . $token, $this->userData);
        $response->assertStatus(200);
    }
    
    public function test_register_token_confirmation_invalid_params(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        foreach(array_keys($this->userData) as $column)
        {
            $data = $this->userData;
            unset($data[$column]);
            
            $data = $this->userData;
            unset($data["firstname"]);
            $response = $this->postJson('/api/register/confirm/' . $token, $data);
            $response->assertStatus(422);
        }
        
        // not match password
        $data = $this->userData;
        $data['password_confirmation'] = "123456789";
        $response = $this->postJson('/api/register/confirm/' . $token, $data);
        $response->assertStatus(422);
    }
    
    public function test_register_token_confirmation_invalid_token(): void
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->postJson('/api/register/confirm/' . "INVALID:TOKEN", $this->userData);
        $response->assertStatus(422);
    }
    
    public function test_register_email_exists(): void
    {
        $this->test_register_token_confirmation_successfull();
        $response = $this->postJson('/api/register', ['email' => $this->testEmail]);
        $response->assertStatus(422);
    }
}
