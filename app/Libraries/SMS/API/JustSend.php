<?php

namespace App\Libraries\SMS\API;

use Illuminate\Support\Facades\Http;
use App\Exceptions\Exception;
use App\Libraries\SMS\SmsInterface;

class JustSend implements SmsInterface
{
    private $token = null;
    private $serviceUrl = null;
    
    public function initialize($config = [])
    {
        $this->serviceUrl = "https://justsend.pl/api/rest/v2/";
        $this->token = env("JUST_SEND_API");
        
        return $this;
    }
    
    public function send(string $number, string $text)
    {
        $data = [
            "to" => $number,
            "from" => "ninjaTask",
            "message" => $text,
            "bulkVariant" => "PRO",
            "doubleEncode" => false,
        ];
        
        $response =
            Http::withOptions(["headers" => $this->getAuthHeaders()])
                ->withBody(json_encode($data), "application/json")
                ->post($this->serviceUrl . "message/send");
            
        if($response->ok() == 200)
        {
            $json = $response->json();
            if($json["message"] == "Successful")
            {
                echo "TODO: zapis i zdjecie z limitu";
            }
        }
    }
    
    private function getAuthHeaders()
    {
        return [
            "App-Key" => $this->token,
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ];
    }
}