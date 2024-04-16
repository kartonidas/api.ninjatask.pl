<?php

namespace App\Interfaces;

interface Template
{
    public function generatePDF();
    public function generateHtml($content);
    public static function getType();
    public static function getName();
    public function getFilename();
    public function getTitle();
    public static function getAvailableVars($array = false, $global = true);
    public function getData();
    public static function getClassObject();
}
