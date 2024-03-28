<?php

namespace App\Libraries\SMS\API;

use Illuminate\Support\Facades\Http;
use App\Exceptions\Exception;
use App\Libraries\SMS\SmsAbstract;
use App\Models\SmsHistory;

class JustSend extends SmsAbstract
{
    private $token = null;
    private $serviceUrl = null;
    
    public function initialize($config = [])
    {
        $this->serviceUrl = "https://justsend.pl/api/rest/v2/";
        $this->token = env("JUST_SEND_API");
        
        return $this;
    }
    
    public function getType(): string
    {
        return "JustSend";
    }
    
    public function getServiceAllowedHours(): array|null
    {
        return [8, 22];
    }
    
    public function send(string $number, string $text): array
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
                $this->log(SmsHistory::STATUS_OK, $number, $text, self::calculateSingleMessage($text));
                return ["status" => true, "used" => self::calculateSingleMessage($text)];
            }
            else
                $this->log(SmsHistory::STATUS_ERR, $number, $text, 0, $json["message"]);
        }
        
        return ["status" => false];
    }
    
    private function getAuthHeaders()
    {
        return [
            "App-Key" => $this->token,
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ];
    }
    
    private static function calculateSingleMessage($text)
    {
        return ceil(mb_strlen($text) / 160);
    }
}