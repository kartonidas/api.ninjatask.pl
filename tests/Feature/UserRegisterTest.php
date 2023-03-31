<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserRegisterTest extends TestCase
{
    public function test_without_email_params(): void
    {
        $response = $this->post('/api/register');
        $response->assertStatus(302);
    }
    
    public function test_invalid_email_params(): void
    {
        $response = $this->post('/api/register', ['email' => 'invalid']);
        $response->assertStatus(302);
    }
    
    public function test_register_successfull(): void
    {
        $response = $this->post('/api/register', ['email' => 'artur.patu.r.a@gmail.com']);
        $response->assertStatus(200);
    }
    
    public function test_register_email_exists(): void
    {
        $response = $this->post('/api/register', ['email' => 'arturpatura@gmail.com']);
        $response->assertStatus(302);
    }
}
