<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Firm;
use App\Models\Invoice;
use App\Models\Subscription;

class Order extends Model
{
    public function getOrderAccountFirmData()
    {
        return Firm::where("uuid", $this->uuid)->first();
    }
    
    public function finish()
    {
        if($this->status == "new")
        {
            $this->status = "finished";
            $this->paid = date("Y-m-d H:i:s");
            $this->save();
            
            $createInvoice = false;

            switch($this->type) {
                case "subscription":
                    Subscription::addPackageFromOrder($this);
                    $createInvoice = true;
                break;
            
                case "sms":
                    SmsPackage::deposit($this->uuid, $this->sms);
                    $createInvoice = true;
                break;
            }
            
            if($createInvoice)
            {
                $invoiceId = Invoice::createInvoice($this);
                $this->invoice_id = $invoiceId;
                $this->save();
            }
        }
    }
}