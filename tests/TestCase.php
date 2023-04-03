<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Models\UserRegisterToken;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected $testEmail = 'arturpatura@gmail.com';
    protected $userData = [
        'firstname' => 'Artur',
        'lastname' => 'Patura',
        'phone' => '723310782',
        'firm_identifier' => 'netextend.pl',
        'password' => 'Pass102@',
        'password_confirmation' => 'Pass102@',
    ];
    protected $workerData = [
        [
            'firstname' => 'Jan',
            'lastname' => 'Kowalski',
            'email' => 'jan-kowalski@gmail.com',
            'phone' => '879546254',
            'superuser' => false,
            'password' => 'Pass102@',
            'password_confirmation' => 'Pass102@',
        ],
        [
            'firstname' => 'Grzegorz',
            'lastname' => 'Wąs',
            'email' => 'grzegorz-was@gmail.com',
            'phone' => '125963544',
            'superuser' => true,
            'password' => 'Pass102@',
            'password_confirmation' => 'Pass102@',
        ],
        [
            'firstname' => 'Zbigniew',
            'lastname' => 'Nowak',
            'email' => 'zbigniew-nowak@gmail.com',
            'phone' => '878555415',
            'superuser' => false,
            'password' => 'Pass102@',
            'password_confirmation' => 'Pass102@',
        ],
    ];
    
    protected function userRegister()
    {
        $response = $this->post('/api/register', ['email' => $this->testEmail]);
        $status = $response->getContent();
        
        if($status !== "1")
            throw new Exception('Invalid response status');
    }
    
    protected function getToken()
    {
        $user = User::where('email', $this->testEmail)->where('owner', 1)->first();
        if(!$user)
            throw new Exception('User not exists');
        
        $tokenRow = UserRegisterToken::where('user_id', $user->id)->first();
        if(!$tokenRow)
            throw new Exception('Token not exists');
        
        return $tokenRow->token;
    }
    
    protected function userRegisterWithConfirmation()
    {
        $this->userRegister();
        $token = $this->getToken();
        
        $response = $this->post('/api/register/confirm/' . $token, $this->userData);
        $response->getContent();
    }
    
    protected function getOwnerLoginToken()
    {
        $this->userRegisterWithConfirmation();
        $data = [
            'email' => $this->testEmail,
            'password' => $this->userData['password'],
            'device_name' => 'test'
        ];
        $response = $this->post('/api/login', $data);
        return $response->getContent();
    }
}
