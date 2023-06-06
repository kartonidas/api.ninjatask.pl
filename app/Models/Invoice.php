<?php

namespace App\Models;

use PDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Firm;
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
    
    public static function getInvoiceData()
    {
        return config("api.invoice");
    }

    public static function getInvoiceDir()
    {
         return storage_path() . "/invoice/";
    }

    public static function createInvoice($order_id, $date, $uuid, $items)
    {
        $date = strtotime($date);

        $totalAmount = $totalGross = 0;
        foreach($items as $item)
        {
            $totalAmount += $item["amount"];
            $totalGross += $item["gross"];
        }

        $accountFirmData = Firm::where("uuid", $uuid)->withoutGlobalScopes()->first();
        if(!$accountFirmData)
            throw new \Exception("Wystąpił nieokreślony błąd!");

        $inv = new Invoice;
        $inv->withoutGlobalScopes();
        $inv->uuid = $uuid;
        $inv->order_id = $order_id;
        $inv->setInvoiceNumber($date);
        $inv->date = date("Y-m-d", $date);
        $inv->nip = $accountFirmData->nip;
        $inv->name = $accountFirmData->name;
        $inv->street = $accountFirmData->street;
        $inv->house_no = $accountFirmData->house_no;
        $inv->apartment_no = $accountFirmData->apartment_no;
        $inv->zip = $accountFirmData->zip;
        $inv->city = $accountFirmData->city;
        $inv->amount = $totalAmount;
        $inv->gross = $totalGross;
        $inv->items = serialize($items);
        $inv->saveQuietly();

        $inv->generateInvoice(true);
        
        $ownerAccount = $accountFirmData->getOwner();
        if($ownerAccount)
            Notification::notify($ownerAccount->id, $inv->id, "invoice:generated");

        return $inv->id;
    }

    private function setInvoiceNumber($date)
    {
        $month = date("n", $date);
        $year = date("Y", $date);

        $number = self::withoutGlobalScopes()->where("month", $month)->where("year", $year)->max("number");

        $this->number =  $number + 1;
        $this->month =  $month;
        $this->year =  $year;
        $this->full_number = sprintf("%s/%s/%s", $this->number, $this->month, $this->year);
    }

    public function generateInvoice($save = false, $force = false)
    {
        if(!$this->generated || $force) {
            $html = view("pdf.invoice", ["data" => $this]);

            $mpdf = PDF::loadView("pdf.invoice", ["data" => $this]);
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
}
