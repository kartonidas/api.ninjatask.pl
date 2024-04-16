<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public static $sortable = ["title"];
    public static $defaultSortable = ["created_at", "desc"];
    
    protected $casts = [
        "created_at" => 'datetime:Y-m-d H:i',
        "updated_at" => 'datetime:Y-m-d H:i',
    ];
    
    protected $hidden = ["uuid"];
}