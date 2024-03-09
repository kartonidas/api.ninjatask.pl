<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Libraries\Helper;
use App\Models\CustomerInvoice;

class CustomerInvoiceItem extends Model
{
    protected $casts = [
        "vat_value" => "string",
        "quantity" => "float",
        "net_amount" => "float",
        "total_net_amount" => "float",
        "gross_amount" => "float",
        "total_gross_amount" => "float",
        "discount" => "float",
        "net_amount_discount" => "float",
        "gross_amount_discount" => "float",
        "total_net_amount_discount" => "float",
        "total_gross_amount_discount" => "float",
        "vat_amount" => "float",
    ];
    
    public static function addItems(CustomerInvoice $invoice, $items = [], $force = false)
    {
        $usedIds = [-1];
        foreach($items as $item)
        {
            $row = !empty($item["id"]) ? self::find($item["id"]) : null;
            if(!$row || $row->customer_invoice_id != $invoice->id)
            {
                if(!$force && !CustomerInvoice::checkOperation($invoice, "item:add"))
                    continue;

                $row = new CustomerInvoiceItem;
                $row->customer_invoice_id  = $invoice->id;
            }
            else
            {
                $usedIds[] = $row->id;
                if(!CustomerInvoice::checkOperation($invoice, "item:update"))
                    continue;
            }

            $row->gtu = $item["gtu"] ?? "";
            $row->name = $item["name"];
            $row->quantity = $item["quantity"];
            $row->unit_type = $item["unit_type"];
            $row->net_amount = $item["net_amount"];
            $row->total_net_amount = $item["net_amount"] * $row->quantity;
            $row->vat_value = $item["vat_value"];
            $row->gross_amount = Helper::calculateGrossAmount($item["net_amount"], $item["vat_value"]);
            $row->total_gross_amount = $row->gross_amount * $row->quantity;
            $row->vat_amount = $row->total_gross_amount - $row->total_net_amount;
            $row->discount = !empty($item["discount"]) && $item["discount"] > 0 ? $item["discount"] : 0;
            if($row->discount > 0)
            {
                $row->net_amount_discount = $row->net_amount - ($row->net_amount * $row->discount / 100);
                $row->gross_amount_discount = Helper::calculateGrossAmount($row->net_amount_discount, $item["vat_value"]);
                $row->total_net_amount_discount = $row->net_amount_discount * $row->quantity;
                $row->total_gross_amount_discount = $row->gross_amount_discount * $row->quantity;
            }
            
            $row->save();

            $usedIds[] = $row->id;
        }

        if(CustomerInvoice::checkOperation($invoice, "item:delete"))
            self::where("customer_invoice_id", $invoice->id)->whereNotIn("id", $usedIds)->delete();
    }

    public static function calculateItemsAmount($items = [])
    {
        $out = [
            "net" => 0,
            "gross" => 0,
            "net_discount" => 0,
            "gross_discount" => 0,
        ];

        $usedIds = [-1];
        foreach($items as $item)
        {
            $item["quantity"] = round(str_replace(",", ".", $item["quantity"]), 2);
            $item["net_amount"] = round(str_replace(",", ".", $item["net_amount"]), 2);
            $item["discount"] = round(str_replace(",", ".", $item["discount"]), 2);

            if(!empty($item["discount"]) && floatval($item["discount"]) > 0)
            {
                $discount = round($item["net_amount"] * floatval($item["discount"]) / 100, 2);
                $item["net_amount"] =  round($item["net_amount"] - $discount, 2);

                $out["net_discount"] += round($discount * $item["quantity"], 2);
                $out["gross_discount"] += round(Helper::calculateGrossAmount($discount, $item["vat_rate_id"]) * $item["quantity"], 2);
            }

            $out["net"] += round($item["net_amount"] * $item["quantity"]);
            $out["gross"] += round(Helper::calculateGrossAmount($item["net_amount"], $item["vat_rate_id"]) * $item["quantity"], 2);
        }
        return $out;
    }
}
