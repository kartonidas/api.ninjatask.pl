<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Exceptions\InvalidStatus;
use App\Models\CustomerContact;
use App\Models\Item;
use App\Models\Rental;

class Customer extends Model
{
    use SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const ROLE_CUSTOMER = "customer";
    public const ROLE_TENANT = "tenant";
    public const TYPE_FIRM = "firm";
    public const TYPE_PERSON = "person";
    public const DOCUMENT_TYPE_ID = "id";
    public const DOCUMENT_TYPE_PASSPORT = "passport";
    
    public static $sortable = ["name", "total_items"];
    public static $defaultSortable = ["name", "asc"];
    
    protected $hidden = ["uuid"];
    
    public static function getDocumentTypes()
    {
        return [
            self::DOCUMENT_TYPE_ID => __("Identification"),
            self::DOCUMENT_TYPE_PASSPORT => __("Passport"),
        ];
    }
    
    public function scopeCustomer(Builder $query): void
    {
        $query->where("role", self::ROLE_CUSTOMER);
    }
    
    public function scopeTenant(Builder $query): void
    {
        $query->where("role", self::ROLE_TENANT);
    }
    
    public function canDelete()
    {
        switch($this->role)
        {
            case self::ROLE_CUSTOMER:
                $c1 = Item::where("customer_id", $this->id)->count();
            
                if($c1)
                    return false;
            break;
        
            case self::ROLE_TENANT:
                $c1 = Rental::where("tenant_id", $this->id)->count();
            
                if($c1)
                    return false;
            break;
        }
        
        return true;
    }
    
    public function delete()
    {
        if(!$this->canDelete())
            throw new InvalidStatus(__("Cannot delete object"));
        
        return parent::delete();
    }
    
    public function contacts()
    {
        return $this->hasMany(CustomerContact::class);
    }
    
    public function getContacts()
    {
        $contacts = [
            CustomerContact::TYPE_EMAIL => [],
            CustomerContact::TYPE_PHONE => [],
        ];
        foreach($this->contacts()->get() as $contact)
            $contacts[$contact->type][] = $contact;
        return $contacts;
    }
    
    public function updateContact($data)
    {
        foreach([CustomerContact::TYPE_EMAIL, CustomerContact::TYPE_PHONE] as $type)
        {
            if(isset($data[$type]))
            {
                if(empty($data[$type]))
                    $this->contacts()->where("type", $type)->delete();
                else
                {
                    $usedIds = [-1];
                    foreach($data[$type] as $contact)
                    {
                        if(empty(trim($contact["val"])))
                            continue;
                        
                        $customerContact = $this->contacts()->where("type", $type)->where("val", $contact["val"])->first();
                        if(!$customerContact)
                        {
                            $customerContact = new CustomerContact;
                            $customerContact->type = $type;
                            $customerContact->customer_id = $this->id;
                            $customerContact->prefix = $contact["prefix"] ?? NULL;
                            $customerContact->val = $contact["val"];
                            $customerContact->notification = !empty($contact["notification"]) ? 1 : 0;
                            $customerContact->save();
                        }
                        else
                        {
                            $customerContact->notification = !empty($contact["notification"]) ? 1 : 0;
                            $customerContact->save();
                        }
                        
                        $usedIds[] = $customerContact->id;
                    }
                    $this->contacts()->where("type", $type)->whereNotIn("id", $usedIds)->delete();
                }
            }
        }
    }
}