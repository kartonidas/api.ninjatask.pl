<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Exceptions\InvalidStatus;
use App\Models\CustomerContact;

class Customer extends Model
{
    use SoftDeletes;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const ROLE_CUSTOMER = "customer";
    public const TYPE_FIRM = "firm";
    public const TYPE_PERSON = "person";
    
    public static $sortable = ["name"];
    public static $defaultSortable = ["name", "asc"];
    
    protected $hidden = ["uuid"];
    
    public function scopeCustomer(Builder $query): void
    {
        $query->where("role", self::ROLE_CUSTOMER);
    }
    
    public function canDelete()
    {
        switch($this->role)
        {
            case self::ROLE_CUSTOMER:
                // todo
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
            if(empty($data[$type]))
                $this->contacts()->where("type", $type)->delete();
            else
            {
                $usedIds = [-1];
                foreach($data[$type] as $contact)
                {
                    if(empty(trim($contact["val"])))
                        continue;
                    
                    $customerContact = $this->contacts()->where("type", $type)->where("prefix", $contact["prefix"] ?? null)->where("val", $contact["val"])->first();
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
                        $customerContact->prefix = $contact["prefix"] ?? NULL;
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