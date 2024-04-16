<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use App\Libraries\TemplateManager;
use App\Models\Customer;
use App\Models\Task;
use App\Models\User;

class DocumentTemplate extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    const TYPE_AGREEMENT = "agreement";
    const TYPE_ANNEX = "annex";
    const TYPE_HANDOVER = "handover_protocol";
    const TYPE_OTHER = "other";
    
    public static $sortable = ["title"];
    public static $defaultSortable = ["title", "asc"];
    
    protected $hidden = ["uuid"];
    
    public static function getTypes()
    {
        return [
            self::TYPE_AGREEMENT => __("Agreement"),
            self::TYPE_ANNEX => __("Annex"),
            self::TYPE_HANDOVER => __("Handover protocol"),
            self::TYPE_OTHER => __("Other")
        ];
    }
    
    public function generateDocument(Customer $customer)
    {
        $manager = TemplateManager::getTemplate($customer);
        return $manager->generateHtml($this->content);
    }
}