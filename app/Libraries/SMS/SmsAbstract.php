<?php

namespace App\Libraries\SMS;

use App\Models\SmsHistory;

abstract class SmsAbstract
{
    abstract public function initialize();
    abstract public function getType(): string;
    abstract public function send(string $number, string $text): bool;
    
    public function log($status, $number, $message, $error_message = null)
    {
        $history = new SmsHistory;
        $history->system = $this->getType();
        $history->status = $status;
        $history->number = $number;
        $history->message = $message;
        $history->error_message = $error_message;
        $history->save();
    }
}