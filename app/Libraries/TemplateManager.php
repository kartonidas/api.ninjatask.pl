<?php

namespace App\Libraries;

use Illuminate\Http\Request;
use App\Exceptions\FileNotExist;
use App\Models\UserTemplate;

class TemplateManager
{
    private $object;
    protected static $cachedData = [];
    public static $smsVariables = false;

    public function __construct($object)
    {
        $this->object = $object;
    }

    public static function getAllowedTemplates()
    {
        $printers = [
            \App\Libraries\Templates\Customer::class,
        ];

        $out = [];
        foreach($printers as $printer)
            $out[$printer::getType()] = [$printer::getName(), $printer];
        return $out;
    }

    public static function getTemplate($object)
    {
        $templates = self::getAllowedTemplates();
        foreach($templates as $type => $template)
        {
            $templateClass = $template[1];
            $objectClass = $templateClass::getClassObject();
            if($object instanceof $objectClass)
            {
                return new $templateClass($object);
            }
        }

        throw new FileNotExist();
    }

    public function generatePDF()
    {
        $template = UserTemplate::getTemplate("pdf", static::getType());
        $content = $template ? $template->content : "";
        return $this->generateHtml($content);
    }

    public function generateHtml($content)
    {
        $variables = $this->prepareVariables();
        $render = new Render($content, $variables);
        $content = $render->render();
        return $content;
    }

    public function generateText($content)
    {
        $variables = $this->prepareVariables();
        $render = new Render($content, $variables);
        $content = $render->render(false);
        return $content;
    }

    public function getObject()
    {
        return $this->object;
    }
}
