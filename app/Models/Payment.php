<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use App\Exceptions\Exception;
use App\Models\Order;

class Payment extends Model
{
    public static function generatePaymentRedirectLink(Order $order)
    {
        $url = "";

        $firmData = $order->getOrderAccountFirmData();
        if(!$firmData)
            throw new Error("Brak danych w ustawieniach firmy. Uzupełnij dane i ponów transakcję.");

        $type = config("payments.type");

        $config = config("payments.config." . $type, []);
        if(!$config)
            return "";

        $md5 = bin2hex(openssl_random_pseudo_bytes(16));
        $paymentRow = new self;
        $paymentRow->md5 = $md5;
        $paymentRow->type = $type;
        $paymentRow->order_id = $order->id;
        $paymentRow->amount = $order->gross;
        $paymentRow->save();
        
        switch($type)
        {
            case "paynow":
                $params = array(
                    "amount" => intval($order->gross * 100),
                    "externalId" => $md5,
                    "currency" => "PLN",
                    "description" => "Płatność za zamówienie nr: ". $order->id,
                    "buyer" => [
                        "email" => $firmData->email,
                        "firstName" => $firmData->firstname,
                        "lastName" => $firmData->lastname,
                    ],
                    "continueUrl" => $config["continue_url"] . "?id=" . $md5,
                );
                
                $sig = self::calculatePayNowSignature($config["api_signature"], $params);
                
                $endpoint = "https://api.paynow.pl";
                if($config["sandbox"] ?? false)
                    $endpoint = "https://api.sandbox.paynow.pl";
                
                $response = Http::withHeaders([
                    "Content-Type" => "application/json",
                    "Api-Key" => $config["api_key"],
                    "Signature" => $sig,
                    "Idempotency-Key" => $md5
                ])->post($endpoint . "/v1/payments", $params);
                
                $status = $response->json();
                $url = $status["redirectUrl"] ?? false;
            break;
        }
        
        if(!$url)
            throw new Exception(__("Failed to initiate transaction. Please try again later."));

        return $url;
    }
    
    public static function finishPayment($paymentId)
    {
        $row = self::find($paymentId);
        if($row)
        {
            $order = Order::where("id", $row->order_id)->where("status", "new")->first();
            if($order)
                $order->finish();

            $row->status = "finished";
            $row->finished = date("Y-m-d H:i:s");
            $row->save();
        }
    }

    public static function cancelPayment($paymentId)
    {
        $row = self::find($paymentId);
        if($row)
        {
            $row->status = "canceled";
            $row->finished = date("Y-m-d H:i:s");
            $row->save();
        }
    }

    public static function refundPayment($paymentId)
    {
        $row = self::find($paymentId);
        if($row)
        {
            $row->status = "refunded";
            $row->finished = date("Y-m-d H:i:s");
            $row->save();
        }
    }
    
    public static function calculatePayNowSignature($key, $params) 
    {
        return base64_encode(hash_hmac("sha256", json_encode($params), $key, true));
    }
}