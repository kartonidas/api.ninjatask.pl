<?php

namespace App\Libraries\SMS;

use App\Exceptions\Exception;
use App\Libraries\SMS\API\JustSend;
use App\Libraries\SMS\API\TestSend;

class Sms
{
    public static function send(string $number, string $text): bool
    {
        $api = self::getApi();
        
        if(!$api)
            throw new Exception(__("Invalid API"));
        
        return $api->send($number, $text);
    }
    
    private static function getApi()
    {
        if(env("SMS_ENGINE") == "TestSend")
            return (new TestSend())->initialize();
        
        return (new JustSend())->initialize();
    }
}