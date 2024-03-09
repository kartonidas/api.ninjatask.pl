<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Exceptions\InvalidStatus;
use App\Exceptions\ObjectNotExist;
use App\Http\Requests\SaleRegisterRequest;
use App\Http\Requests\StoreSaleRegisterRequest;
use App\Http\Requests\UpdateSaleRegisterRequest;
use App\Models\User;
use App\Models\SaleRegister;

class SaleRegisterController extends Controller
{
    public function list(SaleRegisterRequest $request)
    {
        User::checkAccess("config:update");
        
        $validated = $request->validated();
        
        $size = $validated["size"] ?? config("api.list.size");
        $skip = isset($validated["first"]) ? $validated["first"] : (($validated["page"] ?? 1)-1)*$size;
        
        $saleRegisterRows = SaleRegister::whereRaw("1=1");
        
        if(!empty($validated["search"]))
        {
            if(!empty($validated["search"]["type"]))
                $saleRegisterRows->where("type", $validated["search"]["type"]);
        }
            
        $total = $saleRegisterRows->count();
        
        $saleRegisterRows = $saleRegisterRows->take($size)
            ->skip($skip)
            ->orderBy("name", "ASC")
            ->get();
        
        foreach($saleRegisterRows as $i => $saleRegisterRow)
            $saleRegisterRows[$i]->can_delete = $saleRegisterRow->canDelete();
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "data" => $saleRegisterRows,
        ];
            
        return $out;
    }

    public function create(StoreSaleRegisterRequest $request)
    {
        User::checkAccess("config:update");

        $validated = $request->validated();

        $saleRegister = new SaleRegister;
        $saleRegister->name = $validated["name"];
        $saleRegister->type = $validated["type"];
        $saleRegister->mask = $validated["mask"];
        $saleRegister->continuation = $validated["continuation"];
        $saleRegister->setIsDefault($validated["is_default"] ?? 0);
        $saleRegister->save();
        
        return $saleRegister->id;
    }

    public function get(Request $request, $id)
    {
        User::checkAccess("config:update");
        
        $saleRegister = SaleRegister::find($id);
        if(!$saleRegister)
            throw new ObjectNotExist(__("Sale register does not exist"));
        
        return $saleRegister;
    }

    public function update(UpdateSaleRegisterRequest $request, $id)
    {
        User::checkAccess("config:update");
        
        $saleRegister = SaleRegister::find($id);
        if(!$saleRegister)
            throw new ObjectNotExist(__("Sale register does not exist"));

        $validated = $request->validated();
        
        foreach($validated as $field => $value)
            $saleRegister->{$field} = $value;
        $saleRegister->save();
        
        return true;
    }

    public function delete(Request $request, $id)
    {
        User::checkAccess("config:update");

        $saleRegister = SaleRegister::find($id);
        if(!$saleRegister)
            throw new ObjectNotExist(__("Sale register does not exist"));

        if(!$saleRegister->canDelete())
            throw new InvalidStatus(__("Cannot delete sale register"));

        $saleRegister->delete();
        return true;
    }
}
