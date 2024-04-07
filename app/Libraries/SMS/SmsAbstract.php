<?php

namespace App\Libraries\SMS;

use App\Models\SmsHistory;

abstract class SmsAbstract
{
    abstract public function initialize();
    abstract public function getType(): string;
    abstract public function send(string $number, string $text): array;
    
    protected $uuid;
    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }
    
    public function log($status, $number, $message, $used = 1, $error_message = null)
    {
        $history = new SmsHistory;
        $history->uuid = $this->uuid;
        $history->system = $this->getType();
        $history->status = $status;
        $history->number = $number;
        $history->message = $message;
        $history->used = $used;
        $history->error_message = $error_message;
        $history->save();
    }
    
    public function getServiceAllowedHours(): array|null
    {
        return null;
    }
}