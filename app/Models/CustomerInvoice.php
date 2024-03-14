<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Exceptions\ObjectNotExist;
use App\Models\Config;
use App\Models\Customer;
use App\Models\CustomerInvoiceItem;
use App\Models\FirmInvoicingData;
use App\Models\Numbering;
use App\Traits\NumberingTrait;

class CustomerInvoice extends Model
{
    use NumberingTrait;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public const TYPE_FIRM = "firm";
    public const TYPE_PERSON = "person";
    
    public const DOCUMENT_TYPE_INVOICE = "invoice";
    public const DOCUMENT_TYPE_PROFORMA = "proforma";
    
    public const PAYMENT_TRANSFER = "transfer";
    public const PAYMENT_CASH = "cash";
    public const PAYMENT_CARD = "card";

    public static function getAllowedDocumentTypes()
    {
        return [
			self::DOCUMENT_TYPE_INVOICE => __("VAT invoice"),
			self::DOCUMENT_TYPE_PROFORMA => __("Proforma"),
		];
    }
    
    public static function getAllowedPaymentTypes()
    {
        return [
			self::PAYMENT_TRANSFER => __("Transfer"),
			self::PAYMENT_CASH => __("Cash"),
            self::PAYMENT_CARD => __("Card"),
		];
    }
    
    protected $casts = [
        "net_amount" => "float",
        "gross_amount" => "float",
        "net_amount_discount" => "float",
        "gross_amount_discount" => "float",
        "total_payments" => "float",
        "balance" => "float",
        "balance_correction" => "float",
    ];
    protected $hidden = ["uuid"];
    
    public static $sortable = ["type", "full_number", "document_date", "net_amount", "gross_amount"];
    public static $defaultSortable = ["document_date", "desc"];
    
    const SOURCE_DIRECT = "direct";

    public function getMaskNumber()
    {
        return self::getMaskNumberStatic($this->type);
    }
    
    public static function getMaskNumberStatic($type)
    {
        $config = Config::getConfig("invoice");
        
        if($type == "proforma")
        {
            $mask = $config["proforma_mask_number"] ?? config("invoice.default_mask.proforma");
            $continuation = $config["proforma_number_continuation"] ?? config("invoice.default_continuation.proforma");
        }
        else
        {
            $mask = $config["invoice_mask_number"] ?? config("invoice.default_mask.invoice");
            $continuation = $config["invoice_number_continuation"] ?? config("invoice.default_continuation.invoice");
        }
        
        $out = [];
        $out["mask"] = $mask;
        $out["continuation"] = $continuation;
        return $out;
    }

    public function addItems($items = [], $force = false)
    {
        CustomerInvoiceItem::addItems($this, $items, $force);

        $summary = $this->calculateSummary();
        $this->net_amount = $summary["net"];
        $this->gross_amount = $summary["gross"];
        $this->net_amount_discount = $summary["net_discount"];
        $this->gross_amount_discount = $summary["gross_discount"];
        $this->balance = $this->gross_amount;
        $this->saveQuietly();
    }

    private function calculateSummary()
    {
        $summary = [
            "net" => 0,
            "gross" => 0,
            "net_discount" => 0,
            "gross_discount" => 0,
        ];

        $items = $this->items()->get();
        if(!$items->isEmpty())
        {

            $usedIds = [-1];
            foreach($items as $item)
            {
                $quantity = $item->quantity;
                $discount = $item->discount;

                if(floatval($discount) > 0)
                {
                    $summary["net"] += $item->total_net_amount_discount;
                    $summary["gross"] += $item->total_gross_amount_discount;

                    $summary["net_discount"] += $item->total_net_amount - $item->total_net_amount_discount;
                    $summary["gross_discount"] += $item->total_gross_amount - $item->total_gross_amount_discount;
                }
                else
                {
                    $summary["net"] += $item->total_net_amount;
                    $summary["gross"] += $item->total_gross_amount;
                }
            }
        }
        return $summary;
    }

    public function canDelete()
    {
        switch($this->type)
        {
            // Jeśli do proformy została wystawiona faktura to nie pozalamy usunąc
            case "proforma":
                if(self::where("proforma_id", $this->id)->count())
                    return false;
            break;

            // Jeśli do faktury wystawiona jest korekta to nie możemy usnąc faktury źródłowej
            case "invoice":
                if($this->correction_id)
                    return false;
            break;
        }
        
        return true;
    }

    public function canMakeFromProforma()
    {
        if($this->type == "proforma" && !self::where("proforma_id", $this->id)->count())
        {
            $config = Config::getConfig("invoice");
            if($this->system != $config["invoicing_type"])
                return false;
            
            return true;
        }

        return false;
    }

    public function getProformaNumber()
    {
        if($this->proforma_id)
        {
            $proforma = self::find($this->proforma_id);
            if($proforma)
                return $proforma->full_number;
        }
        return "";
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerInvoiceItem::class);
    }
    
    public function getGroupedItems()
    {
        $out = [];
        $items = $this->items()->get();

        foreach($items as $item)
        {
            $item = $item->toArray();
            if(empty($out[$item["vat_value"]]))
            {
                $out[$item["vat_value"]] = [
                    "vat_value" => $item["vat_value"],
                    "net_amount" => 0,
                    "gross_amount" => 0,
                    "gross_value" => 0,
                ];
            }

            $netAmount = $item["total_net_amount"];
            $grossAmount = $item["total_gross_amount"];
            if($item["discount"] > 0)
            {
                $netAmount = $item["total_net_amount_discount"];
                $grossAmount = $item["total_gross_amount_discount"];
            }

            $out[$item["vat_value"]]["net_amount"] += $netAmount;
            $out[$item["vat_value"]]["gross_amount"] += $grossAmount;
        }

        foreach($out as $k => $item)
            $out[$k]["gross_value"] = $item["gross_amount"] - $item["net_amount"];

        uasort($out, function($a, $b) {
            if($a["vat_rate_value"] == $b["vat_rate_value"])
                return 0;
            return (intval($a["vat_rate_value"]) > intval($b["vat_rate_value"])) ? -1 : 1;
        });

        return $out;
    }

    public function delete()
    {
        if($this->canDelete())
        {
            return DB::transaction(function () {
                CustomerInvoiceItem::where("customer_invoice_id", $this->id)->delete();
                Numbering::where("type", "invoice")->where("object_id", $this->id)->delete();
    
                if($this->type == "correction")
                {
                    $invoices = self::where("correction_id", $this->id)->get();
                    if(!$invoices->isEmpty())
                    {
                        foreach($invoices as $invoice)
                        {
                            $invoice->correction_id = NULL;
                            $invoice->save();
                        }
                    }
                }
                
                return parent::delete();
            });
        }
    }

    public static function getInvoiceNextNumber($type)
    {
        $currentYear = date("Y");
        $currentMonth = date("m");

        $allowedTypes = CustomerInvoice::getAllowedDocumentTypes();
        if(!isset($allowedTypes[$type]))
            throw new ObjectNotExist(__("Invalid document type"));

        $maskConfig = self::getMaskNumberStatic($type);
        $fullNumber = $maskConfig["mask"];

        $lastNumberQuery = Numbering::where("document_type", $type);
        switch($maskConfig["continuation"])
        {
            case "month":
                $lastNumberQuery->where("date", $currentYear . "-" . $currentMonth);
            break;
            case "year":
                $lastNumberQuery->whereRaw("SUBSTRING(date, 1, 4) = ?", $currentYear);
            break;
        }

        $number = $lastNumberQuery->max("number") + 1;

        preg_match("/@N([1-9]+)?/i", $maskConfig["mask"], $matches);
        if($matches)
            $fullNumber = str_replace($matches[0], !empty($matches[1]) ? str_pad($number, $matches[1], "0", STR_PAD_LEFT) : $number, $fullNumber);

        $fullNumber = str_ireplace("@M", $currentMonth, $fullNumber);
        $fullNumber = str_ireplace("@Y", $currentYear, $fullNumber);

        return $fullNumber;
    }

    public function isLastNumber()
    {
        $lastId = Numbering::where("type", "invoice")->where("document_type", $this->type)->max("id");
        $numberingRow = Numbering::select("id")->where("type", "invoice")->where("object_id", $this->id)->first();

        if($numberingRow->id != $lastId)
            return false;

        return true;
    }

    public static function getAllowedOperations(CustomerInvoice $invoice = null)
    {
        $operations = [
            "update" => true,
            "download" => true,
            "item:add" => true,
            "item:update" => true,
            "item:delete" => true,
        ];

        if($invoice)
        {
            $config = Config::getConfig("invoice");
            if($invoice->system != "app" && $invoice->system != $config["invoicing_type"])
            {
                foreach($operations as $op => $state)
                    $operations[$op] = false;
            }
            else
            {
                switch($invoice->type)
                {
                    case "proforma":
                        if(self::where("proforma_id", $invoice->id)->count())
                        {
                            foreach($operations as $op => $state)
                                $operations[$op] = false;
                        }
                    break;
    
                    case "invoice":
                        if($invoice->correction_id)
                        {
                            $operations["update"] = false;
                            $operations["item:add"] = false;
                            $operations["item:update"] = false;
                            $operations["item:delete"] = false;
                        }
                    break;
                }
            }
        }
        return $operations;
    }

    public static function checkOperation(CustomerInvoice $invoice = null, $operation)
    {
        $allowed = self::getAllowedOperations($invoice);

        if(!empty($allowed[$operation]))
            return true;
        return false;
    }
    
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, "customer_id");
    }

    public function recipient(): BelongsTo
    {
        if(empty($this->recipient_id) || $this->customer_id == $this->recipient_id)
            return $this->customer();
        
        return $this->belongsTo(Customer::class, "recipient_id");
    }
    
    public function payer(): BelongsTo
    {
        if(empty($this->payer_id) || $this->customer_id == $this->payer_id)
            return $this->customer();
        
        return $this->belongsTo(Customer::class, "payer_id");
    }
    
    public function getFirmInvoicingData()
    {
        return FirmInvoicingData::where("uuid", $this->uuid)
            ->where("id", $this->firm_invoicing_data_id)
            ->withoutGlobalScopes()
            ->withTrashed()
            ->first();
    }
    
    public static function getCurrentFirmInvoicingDataId()
    {
        $config = Config::getConfig("invoice");
        $useFirmInvoicingData = !isset($config["use_invoice_firm_data"]) || !empty($config["use_invoice_firm_data"]);
        
        $firmInvoicingData = $useFirmInvoicingData ? FirmInvoicingData::invoice()->first() : FirmInvoicingData::customerInvoice()->first();
        if(!$firmInvoicingData)
            throw new ObjectNotExist(__("No customer invoicing data configured"));
        
        return $firmInvoicingData->id;
    }
}
