<?php

namespace App\Models;

use Exception;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

use App\Casts\DateCast;
use App\Models\Customer;
use App\Models\DocumentCustomerSignature;

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
        return DocumentCustomerSignature::where("document_id", $this->id)->count() > 0;
    }
    
    public function setSignature($signature)
    {
        if($this->hasCustomerSignature())
            throw new Exception(__("Document has signature"));
        
        $signatureRow = new DocumentCustomerSignature;
        $signatureRow->document_id = $this->id;
        $signatureRow->signature = Crypt::encryptString($signature);
        $signatureRow->save();
    }
    
    public function getSignature()
    {
        $signatureRow = DocumentCustomerSignature::where("document_id", $this->id)->first();
        if($signatureRow)
        {
            try {
                return Crypt::decryptString($signatureRow->signature);
            } catch (DecryptException $e) {}
        }
        
        return null;
    }
    
    public function deleteSignature()
    {
        DocumentCustomerSignature::where("document_id", $this->id)->delete();
    }
}