<?php

namespace App\Http\Controllers;

use Throwable;
use App\Exceptions\Exception;
use App\Exceptions\ObjectNotExist;
use App\Exceptions\InvalidStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

use App\Http\Requests\CustomerInvoicesRequest;
use App\Http\Requests\StoreCustomerCorrectionRequest;
use App\Http\Requests\StoreCustomerInvoicesFromProformaRequest;
use App\Http\Requests\StoreCustomerInvoicesRequest;
use App\Http\Requests\UpdateCustomerCorrectionRequest;
use App\Http\Requests\UpdateCustomerInvoiceDataRequest;
use App\Http\Requests\UpdateCustomerInvoicesRequest;
use App\Libraries\Helper;
use App\Libraries\Invoicing\Invoicing;
use App\Models\Config;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FirmInvoicingData;
use App\Models\Subscription;
use App\Models\User;
use App\Traits\Sortable;

class CustomerInvoicesController extends Controller
{
    use Sortable;
    
    public function list(CustomerInvoicesRequest $request, $type = null)
    {
        User::checkAccess("customer_invoices:list");
        
        $validated = $request->validated();
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        
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
            ->skip(($page-1)*$size)
            ->orderBy($orderBy[0], $orderBy[1])
            ->get();
        
        foreach($userInvoices as $k => $userInvoice)
        {
            $userInvoices[$k]->can_delete = $userInvoice->canDelete();
            $userInvoices[$k]->can_update = CustomerInvoice::checkOperation($userInvoice, "update");
            $userInvoices[$k]->can_download = CustomerInvoice::checkOperation($userInvoice, "download");
            $userInvoices[$k]->make_from_proforma = $userInvoice->canMakeFromProforma();
            $userInvoices[$k]->proforma_number = $userInvoice->getProformaNumber();
        }
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $userInvoices,
        ];
            
        return $out;
    }

    public function create(StoreCustomerInvoicesRequest $request)
    {
        Subscription::checkPackage("customer-invoicing");
        User::checkAccess("customer_invoices:create");
        
        $validated = $request->validated();
        $row = DB::transaction(function () use($validated) {
            $config = Config::getConfig("invoice");
            
            $row = new CustomerInvoice;
            $row->system = $config["invoicing_type"];
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
            $row->payment_type = $validated["payment_type"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $validated["language"];
            $row->currency = $validated["currency"];
            $row->save();
    
            $row->addItems($validated["items"]);
            Invoicing::newInvoice($row);
            
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
        $row->can_download = CustomerInvoice::checkOperation($row, "download");
        $row->make_from_proforma = $row->canMakeFromProforma();
        $row->proforma_number = $row->getProformaNumber();
        $row->seller = $row->getFirmInvoicingData();
        
        return $row;
    }

    public function update(UpdateCustomerInvoicesRequest $request, $id)
    {
        Subscription::checkPackage("customer-invoicing");
        User::checkAccess("customer_invoices:update");

        $row = CustomerInvoice::find($id);
        if(!$row)
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
            $row->payment_type = $validated["payment_type"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $validated["language"];
            $row->currency = $validated["currency"];
            $row->save();
    
            $row->addItems($validated["items"]);
            Invoicing::updateInvoice($row);
            
            return $row;
        });

        return true;
    }
    
    public function fromProforma(StoreCustomerInvoicesFromProformaRequest $request, $proformaId)
    {
        Subscription::checkPackage("customer-invoicing");
        User::checkAccess("customer_invoices:create");
        
        $config = Config::getConfig("invoice");
        if(!in_array($config["invoicing_type"], CustomerInvoice::getProformaAllowedSystems()))
            throw new Exception(sprintf(__("Cannot make proforma using %s API"), $config["invoicing_type"]));
        
        $proforma = CustomerInvoice::find($proformaId);
        if(!$proforma || $proforma->type != CustomerInvoice::DOCUMENT_TYPE_PROFORMA)
            throw new ObjectNotExist(__("Proforma does not exist"));
        
        if(!$proforma->canMakeFromProforma())
            throw new ObjectNotExist(__("The proforma invoice has already been issued"));
        
        $validated = $request->validated();
        
        $row = DB::transaction(function () use($validated, $proforma) {
            $row = new CustomerInvoice;
            $row->system = $proforma->system;
            $row->type = CustomerInvoice::DOCUMENT_TYPE_INVOICE;
            $row->firm_invoicing_data_id = $proforma->firm_invoicing_data_id;
            $row->proforma_id = $proforma->id;
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
            $row->payment_type = $validated["payment_type"];
            $row->account_number = $validated["account_number"] ?? null;
            $row->swift_number = $validated["swift_number"] ?? null;
            $row->language = $validated["language"];
            $row->currency = $validated["currency"];
            $row->save();
    
            $row->addItems($validated["items"]);
            Invoicing::makeFromProforma($row);
            
            return $row;
        });
        
        return $row->id;
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
        
        $name = Helper::__no_pl($row->customer_name . "-" . $row->full_number) . ".pdf";
        $pdf = Invoicing::downloadInvoice($row);
        $header = [
            "Content-type" => "application/pdf",
            "Content-Disposition" => "inline; filename=\"" . $name . "\""
        ];
        return Response::make($pdf, 200, $header);
    }
    
    public function settings(Request $request)
    {
        User::checkAccess("customer_invoices:list");
        
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
        
        if(!empty($config["invoicing_type"]))
        {
            switch($config["invoicing_type"])
            {
                case CustomerInvoice::SYSTEM_INFAKT:
                    try
                    {
                        $settings["infakt_api_key"] = Crypt::decryptString($config["infakt_api_key"]) ?? "";
                    }
                    catch(Throwable $e) {}
                break;
            
                case CustomerInvoice::SYSTEM_FAKTUROWNIA:
                    try
                    {
                        $settings["fakturownia_token"] = Crypt::decryptString($config["fakturownia_token"]) ?? "";
                    }
                    catch(Throwable $e) {}
                    $settings["fakturownia_department_id"] = $config["fakturownia_department_id"] ?? "";
                    $settings["fakturownia_domain"] = $config["fakturownia_domain"] ?? "";
                break;
            
                case CustomerInvoice::SYSTEM_WFIRMA:
                    try
                    {
                        $settings["wfirma_access_key"] = Crypt::decryptString($config["wfirma_access_key"]) ?? "";
                        $settings["wfirma_secret_key"] = Crypt::decryptString($config["wfirma_secret_key"]) ?? "";
                    }
                    catch(Throwable $e) {}
                break;
            }
        }
        
        return $settings;
    }
    
    public function settingsUpdate(UpdateCustomerInvoiceDataRequest $request)
    {
        Subscription::checkPackage("customer-invoicing");
        User::checkAccess("customer_invoices:list");
        
        $validated = $request->validated();
        
        Config::saveConfig("invoice", "invoicing_type", $validated["invoicing_type"]);
        if($validated["invoicing_type"] == CustomerInvoice::SYSTEM_APP)
        {
            Config::saveConfig("invoice", "use_invoice_firm_data", !empty($validated["use_invoice_firm_data"]));
            Config::saveConfig("invoice", "invoice_mask_number", $validated["invoice_mask_number"]);
            Config::saveConfig("invoice", "proforma_mask_number", $validated["proforma_mask_number"]);
            Config::saveConfig("invoice", "invoice_number_continuation", $validated["invoice_number_continuation"]);
            Config::saveConfig("invoice", "proforma_number_continuation", $validated["proforma_number_continuation"]);
        }
        elseif($validated["invoicing_type"] == CustomerInvoice::SYSTEM_INFAKT)
        {
            Config::saveConfig("invoice", "infakt_api_key", Crypt::encryptString($validated["infakt_api_key"]));
        }
        elseif($validated["invoicing_type"] == CustomerInvoice::SYSTEM_FAKTUROWNIA)
        {
            Config::saveConfig("invoice", "fakturownia_token", Crypt::encryptString($validated["fakturownia_token"]));
            Config::saveConfig("invoice", "fakturownia_department_id", $validated["fakturownia_department_id"]);
            Config::saveConfig("invoice", "fakturownia_domain", $validated["fakturownia_domain"]);
        }
        elseif($validated["invoicing_type"] == CustomerInvoice::SYSTEM_WFIRMA)
        {
            Config::saveConfig("invoice", "wfirma_access_key", Crypt::encryptString($validated["wfirma_access_key"]));
            Config::saveConfig("invoice", "wfirma_secret_key", Crypt::encryptString($validated["wfirma_secret_key"]));
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
        
        Config::saveConfig("invoice", "is_configured", 1);
        
        return true;
    }
    
    public function getInvoiceNextNumber(Request $request, $type)
    {
        return ["number" => CustomerInvoice::getInvoiceNextNumber($type)];
    }
    
    public function customerInvoiceConfigured(Request $request)
    {
        $config = Config::getConfig("invoice");
        return !empty($config["is_configured"]);
    }
}
