<?php

namespace App\Libraries\SMS;

use App\Exceptions\Exception;
use App\Libraries\SMS\API\JustSend;

class Sms
{
    public static function send(string $number, string $text)
    {
        $api = self::getApi();
        
        if(!$api)
            throw new Exception(__("Invalid API"));
        
        $api->send($number, $text);
    }
    
    private static function getApi()
    {
        return (new JustSend())->initialize();
    }
}