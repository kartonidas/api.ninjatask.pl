<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use App\Exceptions\ObjectNotExist;
use App\Exceptions\InvalidStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Http\Requests\CustomerInvoicesRequest;
use App\Http\Requests\StoreCustomerCorrectionRequest;
use App\Http\Requests\StoreCustomerInvoicesFromProformaRequest;
use App\Http\Requests\StoreCustomerInvoicesRequest;
use App\Http\Requests\UpdateCustomerCorrectionRequest;
use App\Http\Requests\UpdateCustomerInvoiceDataRequest;
use App\Http\Requests\UpdateCustomerInvoicesRequest;
use App\Libraries\CustomerInvoicePrinter;
use App\Models\Config;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FirmInvoicingData;
use App\Models\SaleRegister;
use App\Models\User;
use App\Traits\Sortable;

class CustomerInvoicesController extends Controller
{
    use Sortable;
    
    public function list(CustomerInvoicesRequest $request, $type = null)
    {
        User::checkAccess("customer_invoices:list");
        
        $validated = $request->validated();
        
        $size = $validated["size"] ?? config("api.list.size");
        $skip = isset($validated["first"]) ? $validated["first"] : (($validated["page"] ?? 1)-1)*$size;
        
        $userInvoices = CustomerInvoice::whereRaw("1=1");
        
        if(!empty($validated["search"]))
        {
            if(!empty($validated["search"]["type"]))
                $userInvoices->where("type", $validated["search"]["type"]);
            if(!empty($validated["search"]["number"]))
                $userInvoices->where("full_number", "LIKE", "%" . $validated["search"]["number"] . "%");
            if(!empty($validated["search"]["customer_id"]))
                $userInvoices->where("customer_id", $validated["search"]["customer_id"]);
            if(!empty($validated["search"]["customer_name"]))
                $userInvoices->where("customer_name", "LIKE", "%" . $validated["search"]["customer_name"] . "%");
            if(!empty($validated["search"]["customer_nip"]))
                $userInvoices->where("customer_nip", "LIKE", "%" . $validated["search"]["customer_nip"] . "%");
            if(!empty($validated["search"]["date_from"]))
                $userInvoices->where("document_date", ">=", $validated["search"]["date_from"]);
            if(!empty($validated["search"]["date_to"]))
                $userInvoices->where("document_date", "<=", $validated["search"]["date_to"]);
            if(!empty($validated["search"]["created_user_id"]))
                $userInvoices->where("created_user_id", $validated["search"]["created_user_id"]);
        }
        
        $total = $userInvoices->count();
        
        $orderBy = $this->getOrderBy($request, CustomerInvoice::class, "document_date,desc");
        $userInvoices = $userInvoices->take($size)
            ->skip($skip)
            ->orderBy($orderBy[0], $orderBy[1])
            ->get();
        
        foreach($userInvoices as $k => $userInvoice)
        {
            $userInvoices[$k]->can_delete = $userInvoice->canDelete();
            $userInvoices[$k]->can_update = CustomerInvoice::checkOperation($userInvoice, "update");
            $userInvoices[$k]->sale_register = $userInvoice->saleRegister()->first();
            $userInvoices[$k]->make_from_proforma = $userInvoice->canMakeFromProforma();
            $userInvoices[$k]->proforma_number = $userInvoice->getProformaNumber();
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "data" => $userInvoices,
        ];
            
        return $out;
    }

    public function create(StoreCustomerInvoicesRequest $request)
    {
        User::checkAccess("customer_invoices:create");
        
        $validated = $request->validated();
        
        $row = DB::transaction(function () use($validated) {
            $row = new CustomerInvoice;
            $row->firm_invoicing_data_id = CustomerInvoice::getCurrentFirmInvoicingDataId();
            $row->type = $validated["type"];
            $row->created_user_id = $validated["created_user_id"];
            $row->customer_id = $validated["customer_id"] ?? null;
            $row->customer_type = $validated["customer_type"];
            $row->customer_name = $validated["customer_name"];
            $row->customer_street = $validated["customer_street"];
            $row->customer_house_no = $validated["customer_house_no"] ?? "";
            $row->customer_apartment_no = $validated["customer_apartment_no"] ?? "";
            $row->customer_city = $validated["customer_city"];
            $row->customer_zip = $validated["customer_zip"];
            $row->customer_country = $validated["customer_country"];
            $row->customer_nip = $validated["customer_type"] == CustomerInvoice::TYPE_FIRM ? $validated["customer_nip"] : "";
            $row->comment = $validated["comment"] ?? null;
            $row->document_date = $validated["document_date"];
            $row->sell_date = $validated["sell_date"];
            $row->payment_date = $validated["payment_date"];
            $row->payment_type_id = $validated["payment_type_id"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $validated["language"];
            $row->currency = $validated["currency"];
            $row->save();
    
            $row->addItems($validated["items"]);
            
            return $row;
        });
        
        return $row->id;
    }
    
    public function get(Request $request, $id)
    {
        User::checkAccess("customer_invoices:list");
        
        $row = CustomerInvoice::find($id);
        if(!$row)
            throw new ObjectNotExist(__("Invoice does not exist"));
        
        $row->items = $row->items()->get();
        $row->can_update = CustomerInvoice::checkOperation($row, "update");
        $row->make_from_proforma = $row->canMakeFromProforma();
        $row->proforma_number = $row->getProformaNumber();
        
        return $row;
    }

    public function update(UpdateCustomerInvoicesRequest $request, $id)
    {
        User::checkAccess("customer_invoices:update");

        $row = CustomerInvoice::find($id);
        if(!$row || $row->type == SaleRegister::TYPE_CORRECTION)
            throw new ObjectNotExist(__("Invoice does not exist"));

        if(!CustomerInvoice::checkOperation($row, "update"))
            throw new InvalidStatus(__("Cannot update invoice"));
        
        $validated = $request->validated();

        $row = DB::transaction(function () use($row, $validated) {
            $row->created_user_id = $validated["created_user_id"];
            $row->customer_id = $validated["customer_id"] ?? null;
            $row->customer_type = $validated["customer_type"];
            $row->customer_name = $validated["customer_name"];
            $row->customer_street = $validated["customer_street"];
            $row->customer_house_no = $validated["customer_house_no"] ?? "";
            $row->customer_apartment_no = $validated["customer_apartment_no"] ?? "";
            $row->customer_city = $validated["customer_city"];
            $row->customer_zip = $validated["customer_zip"];
            $row->customer_country = $validated["customer_country"];
            $row->customer_nip = $validated["customer_type"] == CustomerInvoice::TYPE_FIRM ? $validated["customer_nip"] : "";
            $row->comment = $validated["comment"] ?? null;
            $row->document_date = $validated["document_date"];
            $row->sell_date = $validated["sell_date"];
            $row->payment_date = $validated["payment_date"];
            $row->payment_type_id = $validated["payment_type_id"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $validated["language"];
            $row->currency = $validated["currency"];
            $row->save();
    
            $row->addItems($validated["items"]);
            
            return $row;
        });

        return true;
    }
    
    public function fromProforma(StoreCustomerInvoicesFromProformaRequest $request, $proformaId)
    {
        User::checkAccess("customer_invoices:create");
        
        $proforma = CustomerInvoice::find($proformaId);
        if(!$proforma || $proforma->type != SaleRegister::TYPE_PROFORMA)
            throw new ObjectNotExist(__("Proforma does not exist"));
        
        if(!$proforma->canMakeFromProforma())
            throw new ObjectNotExist(__("The proforma invoice has already been issued"));
        
        $validated = $request->validated();
        
        $row = DB::transaction(function () use($validated, $proforma) {
            $row = new CustomerInvoice;
            $row->type = SaleRegister::TYPE_INVOICE;
            $row->firm_invoicing_data_id = $proforma->firm_invoicing_data_id;
            $row->proforma_id = $proforma->id;
            $row->sale_register_id = $validated["sale_register_id"];
            $row->created_user_id = $validated["created_user_id"];
            $row->customer_id = $validated["customer_id"] ?? null;
            $row->customer_type = $validated["customer_type"];
            $row->customer_name = $validated["customer_name"];
            $row->customer_street = $validated["customer_street"];
            $row->customer_house_no = $validated["customer_house_no"] ?? "";
            $row->customer_apartment_no = $validated["customer_apartment_no"] ?? "";
            $row->customer_city = $validated["customer_city"];
            $row->customer_zip = $validated["customer_zip"];
            $row->customer_country = $validated["customer_country"];
            $row->customer_nip = $validated["customer_type"] == CustomerInvoice::TYPE_FIRM ? $validated["customer_nip"] : "";
            $row->comment = $validated["comment"] ?? null;
            $row->document_date = $validated["document_date"];
            $row->sell_date = $validated["sell_date"];
            $row->payment_date = $validated["payment_date"];
            $row->payment_type_id = $validated["payment_type_id"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $validated["language"];
            $row->currency = $validated["currency"];
            $row->save();
    
            $row->addItems($validated["items"]);
            
            return $row;
        });
        
        return $row->id;
    }

    public function correctionCreate(StoreCustomerCorrectionRequest $request, $invoiceId)
    {
        User::checkAccess("customer_invoices:create");

        $invoice = CustomerInvoice::find($invoiceId);
        if(!$invoice)
            throw new ObjectNotExist(__("Invoice to correction does not exist"));
            
        if(!$invoice->canMakeCorrection())
            throw new ObjectNotExist(__("Cannot correction selected invoice"));
        
        $validated = $request->validated();

        $row = DB::transaction(function () use($validated, $invoice) {
            $row = new CustomerInvoice;
            $row->firm_invoicing_data_id = CustomerInvoice::getCurrentFirmInvoicingDataId();
            $row->type = SaleRegister::TYPE_CORRECTION;
            $row->sale_register_id = $validated["sale_register_id"];
            $row->created_user_id = $validated["created_user_id"];
            $row->customer_id = $invoice->customer_id ?? null;
            $row->customer_type = $invoice->customer_type;
            $row->customer_name = $invoice->customer_name;
            $row->customer_street = $invoice->customer_street;
            $row->customer_house_no = $invoice->customer_house_no;
            $row->customer_apartment_no = $invoice->customer_apartment_no;
            $row->customer_city = $invoice->customer_city;
            $row->customer_zip = $invoice->customer_zip;
            $row->customer_country = $invoice->customer_country;
            $row->customer_nip = $invoice->customer_nip;
            $row->comment = $validated["comment"] ?? null;
            $row->document_date = $validated["document_date"];
            $row->sell_date = $validated["sell_date"];
            $row->payment_date = $validated["payment_date"];
            $row->payment_type_id = $invoice->payment_type_id;
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $invoice->language;
            $row->currency = $invoice->currency;
            $row->save();
    
            $invoice->correction_id = $row->id;
            $invoice->save();
    
            $row->addItems($validated["items"], true);
            
            return $row;
        });
        
        return $row->id;
    }

    public function correctionUpdate(UpdateCustomerCorrectionRequest $request, $correctionId)
    {
        User::checkAccess("customer_invoices:update");

        $row = CustomerInvoice::find($correctionId);
        if(!$row || $row->type != SaleRegister::TYPE_CORRECTION)
            throw new ObjectNotExist(__("Correction does not exist"));

        $validated = $request->validated();
            
        $row = DB::transaction(function () use($validated, $row) {
            $row->created_user_id = $validated["created_user_id"];
            $row->comment = $validated["comment"] ?? null;
            $row->document_date = $validated["document_date"];
            $row->sell_date = $validated["sell_date"];
            $row->payment_date = $validated["payment_date"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->save();
            
            $row->addItems($validated["items"]);
    
            return $row;
        });
        
        return true;
    }

    public function delete(Request $request, $id)
    {
        User::checkAccess("customer_invoices:delete");

        $row = CustomerInvoice::find($id);
        if(!$row)
            throw new ObjectNotExist(__("Invoice does not exist"));

        if(!$row->canDelete())
            throw new InvalidStatus(__("Cannot delete invoice"));

        $row->delete();
        
        return true;
    }

    public function getPdf(Request $request, $id)
    {
        User::checkAccess("customer_invoices:list");

        $row = CustomerInvoice::find($id);
        if(!$row)
            throw new ObjectNotExist(__("Invoice does not exist"));

        CustomerInvoicePrinter::generatePDF($row);
    }
    
    public function settings(Request $request)
    {
        User::checkAccess("config:update");
        
        $config = Config::getConfig("invoice");
        $useInvoiceFirmData = !isset($config["use_invoice_firm_data"]) || !empty($config["use_invoice_firm_data"]);
        if($useInvoiceFirmData)
            $invoiceData = FirmInvoicingData::invoice()->first();
        else
            $invoiceData = FirmInvoicingData::customerInvoice()->first();
        
        $settings = [
            "type" => $invoiceData->type ?? "person",
            "nip" => $invoiceData->nip ?? "",
            "name" => $invoiceData->name ?? "",
            "firstname" => $invoiceData->firstname ?? "",
            "lastname" => $invoiceData->lastname ?? "",
            "street" => $invoiceData->street ?? "",
            "house_no" => $invoiceData->house_no ?? "",
            "apartment_no" => $invoiceData->apartment_no ?? "",
            "city" => $invoiceData->city ?? "",
            "zip" => $invoiceData->zip ?? "",
            "country" => $invoiceData->country ?? "PL",
            "use_invoice_firm_data" => $useInvoiceFirmData,
            "invoicing_type" => !empty($config["invoicing_type"]) ? $config["invoicing_type"] : "app",
            "invoice_mask_number" => !empty($config["invoice_mask_number"]) ? $config["invoice_mask_number"] : config("invoice.default_mask.invoice"),
            "proforma_mask_number" => !empty($config["proforma_mask_number"]) ? $config["proforma_mask_number"] : config("invoice.default_mask.proforma"),
            "invoice_number_continuation" => !empty($config["invoice_number_continuation"]) ? $config["invoice_number_continuation"] : config("invoice.default_continuation.invoice"),
            "proforma_number_continuation" => !empty($config["proforma_number_continuation"]) ? $config["proforma_number_continuation"] : config("invoice.default_continuation.proforma"),
        ];
        
        if(!empty($config["invoicing_type"]) && $config["invoicing_type"] == "infakt")
        {
            try
            {
                $settings["infakt_api_key"] = Crypt::decryptString($config["infakt_api_key"]) ?? "";
            }
            catch(Throwable $e) {}
        }
        
        if(!empty($config["invoicing_type"]) && $config["invoicing_type"] == "fakturownia")
        {
            try
            {
                $settings["fakturownia_token"] = Crypt::decryptString($config["fakturownia_token"]) ?? "";
            }
            catch(Throwable $e) {}
            $settings["fakturownia_department_id"] = $config["fakturownia_department_id"] ?? "";
            $settings["fakturownia_domain"] = $config["fakturownia_domain"] ?? "";
        }
        
        return $settings;
    }
    
    public function settingsUpdate(UpdateCustomerInvoiceDataRequest $request)
    {
        User::checkAccess("config:update");
        
        $validated = $request->validated();
        
        Config::saveConfig("invoice", "invoicing_type", $validated["invoicing_type"]);
        if($validated["invoicing_type"] == "app")
        {
            Config::saveConfig("invoice", "use_invoice_firm_data", !empty($validated["use_invoice_firm_data"]));
            Config::saveConfig("invoice", "invoice_mask_number", $validated["invoice_mask_number"]);
            Config::saveConfig("invoice", "proforma_mask_number", $validated["proforma_mask_number"]);
        }
        elseif($validated["invoicing_type"] == "infakt")
        {
            Config::saveConfig("invoice", "infakt_api_key", Crypt::encryptString($validated["infakt_api_key"]));
        }
        elseif($validated["invoicing_type"] == "fakturownia")
        {
            Config::saveConfig("invoice", "fakturownia_token", Crypt::encryptString($validated["fakturownia_token"]));
            Config::saveConfig("invoice", "fakturownia_department_id", $validated["fakturownia_department_id"]);
            Config::saveConfig("invoice", "fakturownia_domain", $validated["fakturownia_domain"]);
        }
        
        if($validated["invoicing_type"] == "app" && empty($validated["use_invoice_firm_data"]))
        {
            $invoicingData = FirmInvoicingData::customerInvoice()->first();
            if(!$invoicingData)
            {
                $invoicingData = new FirmInvoicingData;
                $invoicingData->object = FirmInvoicingData::OBJECT_CUSTOMER_INVOICE;
            }
            
            foreach($validated as $field => $value)
            {
                if(!Schema::hasColumn($invoicingData->getTable(), $field))
                    continue;
                
                $invoicingData->{$field} = $value;
            }
            
            if($invoicingData->isDirty())
            {
                if($invoicingData->id > 0)
                {
                    $invoicingData->replicate()->save();
                    $invoicingData->delete();
                }
                else
                    $invoicingData->save();
            }
        }
        
        return true;
    }
    
    public function getInvoiceNextNumber(Request $request, $saleRegisterId)
    {
        $saleRegister = SaleRegister::find($saleRegisterId);
        
        if(!$saleRegister)
            throw new ObjectNotExist(__("Sale register does not exist"));
        
        return CustomerInvoice::getInvoiceNextNumber($saleRegister->id);
    }
}
