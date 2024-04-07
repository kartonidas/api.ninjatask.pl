<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use App\Exceptions\Exception;
use App\Exceptions\ObjectNotExist;
use App\Http\Requests\OrderRequest;
use App\Models\FirmInvoicingData;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;

class OrderController extends Controller
{
    /**
    * Create order
    *
    * Create order and return redirect to payment URL.
    * @queryParam package string Package name (one of: p1)
    * @response 200 {"url": "https://..."}
    * @header Authorization: Bearer {TOKEN}
    * @group Orders
    */
    public function create(OrderRequest $request)
    {
        $validInvoicing = FirmInvoicingData::validateInvoicingData();
        if(!$validInvoicing)
            throw new Exception(__("Invalid invoicing data"));
        
        $invoicingData = FirmInvoicingData
            ::where("uuid", Auth::user()->getUuid())
            ->whereNull("deleted_at")
            ->withoutGlobalScopes()
            ->first();
            
        $foreign = strtolower($invoicingData->country) != "pl";
        $reverseCharge = $foreign && $invoicingData->type == "firm";
        
        $validated = $request->validated();
        $packages = config("packages.allowed");
        $package = $packages[$validated["package"]];
        
        if($validated["package"] == "sms")
        {
            $package["price"] = $package["price"] * ($validated["quantity"] / 50);
            $package["sms"] = $validated["quantity"];
        }
        
        $url = DB::transaction(function () use($package, $invoicingData, $reverseCharge) {
            $order = new Order;
            $order->uuid = Auth::user()->getUuid();
            $order->type = $package["type"];
            $order->unit_price = !$reverseCharge ? $package["price"] : round($package["price"] * ((100 + $package["vat"]) / 100), 2);
            $order->unit_price_gross = round($package["price"] * ((100 + $package["vat"]) / 100), 2);
            $order->quantity = 1;
            $order->amount = $order->unit_price * $order->quantity;
            $order->vat = !$reverseCharge ? $package["vat"] : 0;
            $order->gross = $order->unit_price_gross * $order->quantity;
            $order->name = $package["name"];
            $order->months = $package["months"];
            $order->sms = $package["sms"] ?? 0;
            $order->firm_invoicing_data_id = $invoicingData->id;
            $order->reverse = $reverseCharge ? 1 : 0;
            $order->save();
            
            return Payment::generatePaymentRedirectLink($order);
        });
        
        return [
            "url" => $url,
        ];
    }
    
    /**
    * Get invoices list
    *
    * Return invoices list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": [{"order_id": 1, "full_number", "date": "2022-12-11", "nip": "0123456789", "name": "John Doe", "street": "Street name", "house_no": 12, "apartment_no": "1A", "zip": "00-123", "city": "London", "amount": 100, "gross": 123, "items": [], "generated": 1, "created_at": "2023-01-01 10:00:00"}]}
    * @header Authorization: Bearer {TOKEN}
    * @group Orders
    */
    public function invoices(Request $request)
    {
        if(!Auth::user()->owner)
            throw new AccessDenied(__("Access denied"));
        
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
        $invoices = Invoice
            ::apiFields()
            ->where("generated", 1)
            ->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("id", "DESC")
            ->get();
            
        $total = Invoice::where("generated", 1)->count();
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $invoices,
        ];
            
        return $out;
    }
    
    /**
    * Get invoice PDF
    *
    * Return invoice pdf.
    * @header Authorization: Bearer {TOKEN}
    * @group Orders
    */
    public function invoice(Request $request, $id)
    {
        if(!Auth::user()->owner)
            throw new AccessDenied(__("Access denied"));
        
        $invoice = Invoice::find($id);
        if(!$invoice || !$invoice->generated)
            throw new ObjectNotExist(__("Invoice does not exist"));
        
        
        $dir = Invoice::getInvoiceDir() . $invoice->uuid . "/";
        $file = $dir . $invoice->file;

        if(!file_exists($file))
            throw new ObjectNotExist(__("Invoice does not exist"));

        return [
            "invoice" => base64_encode(file_get_contents($file)),
            "name" => $invoice->file,
        ];
    }
    
    /**
    * Validate invoicing data
    *
    * Validate invoicing data.
    * @header Authorization: Bearer {TOKEN}
    * @group Orders
    */
    public function validateInvoicingData(Request $request)
    {
        return ["valid" => FirmInvoicingData::validateInvoicingData()];
    }
}