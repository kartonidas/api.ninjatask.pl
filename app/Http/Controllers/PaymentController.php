<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Exceptions\ObjectNotExist;
use App\Models\Order;
use App\Models\Payment;

class PaymentController extends Controller
{
    // Metoda wywolywana poza API
    public function ipnPaynow(Request $request)
    {
        $json = file_get_contents("php://input");
        $result = json_decode($json);
        
        $md5 = $result->externalId;
        $data = Payment::where("md5", $md5)->first();
        if(!$data || $data->type != "paynow")
            exit;
            
        $config = config("payments.config.paynow");
        if(!$config)
            exit;
        
        $sig = Payment::calculatePayNowSignature($config["api_signature"], (array)$result);
        
        if($sig != $request->header("signature"))
            exit;
        
        $status = strtoupper($result->status);
        if($status == "REJECTED" || $status == "ERROR")
        {
            Payment::cancelPayment($data->id);
        }
        elseif($status == "CONFIRMED")
        {
            if($data->status == "new")
                Payment::finishPayment($data->id);
        }
        
        exit;
    }
    
    /**
    * Get payment status
    *
    * Return payment status.
    * @response 200 {"status": "finished"}
    * @header Authorization: Bearer {TOKEN}
    * @group Payments
    */
    public function status(Request $request, $md5)
    {
        $status = Payment::where("md5", $md5)->first();
        if(!$status)
            throw new ObjectNotExist(__("Payment not exists"));
    
        $order = Order::find($status->order_id);
        if(!$order)
            throw new ObjectNotExist(__("Order not exists"));
    
        return [
            "status" => $status->status
        ];
    }
}
