<?php

namespace App\Models;

use PDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Firm;
use App\Models\FirmInvoicingData;
use App\Models\InvoiceData;
use App\Models\Order;
use App\Traits\DbTimestamp;

class Invoice extends Model
{
    use DbTimestamp;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "order_id", "full_number", "date", "nip", "name", "street", "house_no", "apartment_no", "zip","city", "amount", "gross", "items", "generated", "created_at");
    }
    
    private static function getActiveInvoiceDataId()
    {
        return InvoiceData::getActiveData()->id;
    }
    
    public function getInvoiceData()
    {
        return InvoiceData::find($this->invoice_data_id);
    }

    public static function getInvoiceDir()
    {
         return storage_path() . "/invoice/";
    }

    public static function createInvoice(Order $order)
    {
        $items = [
            [
                "name" => $order->name,
                "amount" => $order->amount,
                "vat" => $order->vat,
                "gross" => $order->gross,
                "qt" => 1,
            ]
        ];
            
        $accountFirmData = Firm::where("uuid", $order->uuid)->withoutGlobalScopes()->first();
        if(!$accountFirmData)
            throw new \Exception("Wystąpił nieokreślony błąd!");
        
        $invoicingData = FirmInvoicingData::where("uuid", $order->uuid)
            ->where("id", $order->firm_invoicing_data_id)
            ->withoutGlobalScopes()
            ->withTrashed()
            ->first();
            
        if(!$invoicingData)
            throw new \Exception("Wystąpił nieokreślony błąd!");
        
        $date = strtotime($order->paid);
        $foreign = strtolower($invoicingData->country) != "pl";

        $totalAmount = $totalGross = 0;
        foreach($items as $item)
        {
            $totalAmount += $item["amount"];
            $totalGross += $item["gross"];
        }
        
        $inv = new Invoice;
        $inv->withoutGlobalScopes();
        $inv->uuid = $order->uuid;
        $inv->invoice_data_id = self::getActiveInvoiceDataId();
        $inv->order_id = $order->id;
        $inv->type = $invoicingData->type;
        $inv->setInvoiceNumber($date);
        $inv->date = date("Y-m-d", $date);
        if($invoicingData->type == "firm")
        {
            $inv->nip = $invoicingData->nip;
            $inv->name = $invoicingData->name;
        }
        else
        {
            $inv->firstname = $invoicingData->firstname;
            $inv->lastname = $invoicingData->lastname;
        }
        $inv->street = $invoicingData->street;
        $inv->house_no = $invoicingData->house_no;
        $inv->apartment_no = $invoicingData->apartment_no;
        $inv->zip = $invoicingData->zip;
        $inv->city = $invoicingData->city;
        $inv->country = $invoicingData->country;
        $inv->amount = $totalAmount;
        $inv->gross = $totalGross;
        $inv->items = serialize($items);
        $inv->lang = $foreign ? "en" : "pl";
        $inv->reverse = $order->reverse;
        $inv->firm_invoicing_data_id = $order->firm_invoicing_data_id;
        $inv->currency = "PLN";
        $inv->saveQuietly();

        $inv->generateInvoice(true);
        
        $ownerAccount = $accountFirmData->getOwner();
        if($ownerAccount)
            Notification::notify($ownerAccount->id, -1, $inv->id, "invoice:generated");

        return $inv->id;
    }

    private function setInvoiceNumber($date)
    {
        $month = date("n", $date);
        $year = date("Y", $date);

        $number = self::withoutGlobalScopes()->where("month", $month)->where("year", $year)->where("type", $this->type)->max("number");

        $this->number =  $number + 1;
        $this->month =  $month;
        $this->year =  $year;
        
        if($this->type == "firm")
            $this->full_number = sprintf("NTF/%s/%s/%s", $this->number, $this->month, $this->year);
        else
            $this->full_number = sprintf("NTR/%s/%s/%s", $this->number, $this->month, $this->year);
    }

    public function generateInvoice($save = false, $force = false)
    {
        if(!$this->generated || $force) {
            $tpl = $this->lang != "pl" ? "pdf.invoice_en" : "pdf.invoice";

            $mpdf = PDF::loadView($tpl, ["data" => $this]);
            $mpdf->getMpdf()->SetTitle("Faktura: " . $this->number);
            $mpdf->getMpdf()->margin_header = 0;

            $filename = "inv_" . str_replace("/", "_", $this->full_number) . ".pdf";

            if(!$save)
                $mpdf->stream($filename, "I");
            else {
                $dir = storage_path("/invoice/" . $this->uuid);
                @mkdir($dir, 0777, true);
                $mpdf->save($dir . "/" . $filename, "F");
                @chmod($dir . "/" . $filename, 0777);

                $this->generated = 1;
                $this->file = $filename;
                $this->save();
            }
        }
    }

    public function getItems()
    {
        return unserialize($this->items);
    }

    public function getGroupedItemsByVat()
    {
        $out = [];
        $items = $this->getItems();
        foreach($items as $item) {
            $netto = $item["amount"] * $item["qt"];
            $vat = $netto*($item["vat"]/100);

            if(!isset($out[$item["vat"]]))
            {
                $out[$item["vat"]]["netto"] = 0;
                $out[$item["vat"]]["vat"] = 0;
                $out[$item["vat"]]["brutto"] = 0;
            }

            $out[$item["vat"]]["netto"] += $netto;
            $out[$item["vat"]]["vat"] += $vat;
            $out[$item["vat"]]["brutto"] += $netto + $vat;

        }
        return $out;
    }
    
    public static function getItemName($item, $language = "pl")
    {
        switch($item)
        {
            case "premium:1":
                return $language == "pl" ? "Dostęp do pełnej wersji serwisu ninjatask.pl (1 miesiąc)" : "Access to the full version of the ninjatask.pl website (1 month)";
            break;
            case "premium:12":
                return $language == "pl" ? "Dostęp do pełnej wersji serwisu ninjatask.pl (12 miesięcy)" : "Access to the full version of the ninjatask.pl website (12 months)";
            break;
        }
        return $item;
    }
}
