<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\Subscription\Activated;
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

            switch($this->type) {
                case "subscription":
                    $subscription = Subscription::addPackageFromOrder($this);
                    
                    $owner = Firm::getOwnerByUuid($this->uuid);
                    if($owner)
                        Mail::to($owner->email)->locale($owner->getLocale())->queue(new Activated($subscription));
                    
                    $items = [];
                    $items[] = [
                        "name" => $this->name,
                        "amount" => $this->amount,
                        "vat" => $this->vat,
                        "gross" => $this->gross,
                        "qt" => 1,
                    ];
                    $invoiceId = Invoice::createInvoice($this->id, $this->paid, $this->uuid, $items);
                    $this->invoice_id = $invoiceId;
                    $this->save();
                break;
            }
        }
    }
}