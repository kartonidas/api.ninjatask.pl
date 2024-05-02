<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
        return $this->hasCustomerSignature() ? false : true;
    }
    
    public function hasCustomerSignature()
    {
        return !empty($this->customer_signature);
    }
    
    public function getSignature()
    {
        if(!empty($this->customer_signature))
        {
            try {
                return Crypt::decryptString($this->customer_signature);
            } catch (DecryptException $e) {}
        }
        
        return null;
    }
}