<?php

namespace App\Http\Controllers;

use DateTime;
use DateInterval;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\ObjectNotExist;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
    * Get notifications list
    *
    * Return notifications list.
    * @queryParam size integer Number of rows. Default: 50
    * @queryParam page integer Number of page (pagination). Default: 1
    * @response 200 {"total_rows": 100, "total_pages": "4", "current_page": 1, "has_more": true, "data": ["id": 1, "object_id": 1, "object_name": "Task #1", "type": "task:assign", "read": 0, "read_time": null, "created_at": "2023-01-01 00:00:00"]}
    * @header Authorization: Bearer {TOKEN}
    * @group Notifications
    */
    public function list(Request $request)
    {
        $request->validate([
            "size" => "nullable|integer|gt:0",
            "page" => "nullable|integer|gt:0",
            "only_unread" => "nullable|boolean",
        ]);
        
        $size = $request->input("size", config("api.list.size"));
        $page = $request->input("page", 1);
        $unread = $request->input("only_unread", false);
        
        $notifications = Notification
            ::apiFields()
            ->where("user_id", Auth::user()->id);
        
        if($unread)
            $notifications->where("read", 0);
        
        $total = $notifications->count();
            
        $notifications = $notifications->take($size)
            ->skip(($page-1)*$size)
            ->orderBy("created_at", "DESC")
            ->get();
        
        $out = [
            "total_rows" => $total,
            "total_pages" => ceil($total / $size),
            "current_page" => $page,
            "has_more" => ceil($total / $size) > $page,
            "data" => $notifications,
        ];
            
        return $out;
    }
    
    /**
    * Set notification as read
    *
    * Set notification as read.
    * @queryParam id integer notification identifier
    * @header Authorization: Bearer {TOKEN}
    * @group Notifications
    */
    public function setRead(Request $request, $id)
    {
        $notification = Notification::where("id", $id)->where("user_id", Auth::user()->id)->first();
        if(!$notification)
            throw new ObjectNotExist(__("Notification not exists"));
        
        if(!$notification->read)
        {
            $notification->read = 1;
            $notification->read_time = date("Y-m-d H:i:s");
            $notification->save();
        }
    }
}