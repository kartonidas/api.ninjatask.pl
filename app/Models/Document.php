<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use App\Casts\DateCast;
use App\Models\Customer;

class Document extends Model
{
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public static $sortable = ["title", "created_at"];
    public static $defaultSortable = ["created_at", "desc"];
    
    protected $casts = [
        "created_at" => DateCast::class,
        "updated_at" => DateCast::class,
    ];
    
    protected $hidden = ["uuid"];
    
    public function getCustomer()
    {
        return Customer::find($this->customer_id);
    }
    
    public function canEdit()
    {
        return true;
    }
}