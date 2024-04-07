<?php

namespace App\Libraries\SMS\API;

use Illuminate\Support\Facades\Http;
use App\Exceptions\Exception;
use App\Libraries\SMS\SmsAbstract;
use App\Models\SmsHistory;

class TestSend extends SmsAbstract
{
    public function initialize($config = [])
    {
        return $this;
    }
    
    public function getType(): string
    {
        return "TestSend";
    }
    
    public function send(string $number, string $text): array
    {
        $data = [
            "to" => $number,
            "from" => "ninjaTask",
            "message" => $text,
        ];
        
        $file = storage_path("logs/test-send.txt");
        $fp = fopen($file, "a");
        fwrite($fp, date("Y-m-d H:i:s") . "\n");
        fwrite($fp, serialize($data) . "\n\n\n");
        fclose($fp);
        
        $this->log(SmsHistory::STATUS_OK, $number, $text, 1);
        
        return ["status" => true, "used" => 1];
    }
}