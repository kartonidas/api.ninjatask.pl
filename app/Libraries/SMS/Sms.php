<?php

namespace App\Libraries\SMS;

use App\Exceptions\Exception;
use App\Libraries\SMS\API\JustSend;
use App\Libraries\SMS\API\TestSend;

class Sms
{
    public static function send(string $uuid, string $number, string $text): array
    {
        $api = self::getApi($uuid);
        
        if(!$api)
            throw new Exception(__("Invalid API"));
        
        return $api->send($number, $text);
    }
    
    private static function getApi(string $uuid)
    {
        if(env("SMS_ENGINE") == "TestSend")
            return (new TestSend($uuid))->initialize();
        
        return (new JustSend($uuid))->initialize();
    }
    
    public static function getServiceAllowedHours()
    {
        $api = self::getApi("");
        return $api->getServiceAllowedHours();
    }
}