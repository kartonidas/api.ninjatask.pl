<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_create_task_successfull(): void
    {
        $this->prepareMultipleUserAccount();
        $data = [
            'email' => $this->getAccount(2)['email'],
            'password' => $this->getAccount(2)['data']['password'],
            'device_name' => 'test',
        ];
        $response = $this->postJson('/api/login', $data);
        $token = $response->getContent();
        
        $response = $this->withToken($token)->getJson('/api/tasks');
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
}