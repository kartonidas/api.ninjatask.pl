<?php

namespace App\Libraries\SMS;

interface SmsInterface
{
    public function initialize();
    public function send(string $number, string $text);
}