<?php

namespace App\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Models\Dictionary;

class Helper
{
    public static function objectToArray($array)
    {
        return json_decode(json_encode($array), true);
    }

    public static function prepareDate($date, $showHour = true) {
        $date = trim($date);
        if($date == "")
            return "";

        if(!self::_validateDate($date) && !self::_validateDate($date, "Y-m-d H:i:s"))
            return $date;

        $p1 = $date;
        $p2 = "";

        if(mb_strlen($date > 10)) {
            $p1 = substr($date, 0, 10);
            $p2 = substr($date, 11);
        }

        if(in_array($p1, ["1900-01-01"]))
            $p1 = "";

        if(in_array($p2, ["00:00:00"]))
            $p2 = "";

        if($showHour && $p1 && $p2)
            return $p1 . " " . $p2;

        return $p1;
    }

    public static function _validateDate($date, $format = "Y-m-d") {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function amount($amount) {
        return number_format($amount, 2, ",", "");
    }

    public static function parseDateTime($date, $type = "date")
    {
        $date = new \DateTime($date);
        switch($type)
        {
            case "date": return $date->format("Y-m-d");
            case "hour": return intval($date->format("H"));
            case "min": return intval($date->format("i"));
        }
    }

    public static function calculateGrossAmount($net_amount, $vat_rate_id)
    {
        $net_amount = str_replace(",", ".", $net_amount);
        if(!is_numeric($net_amount) || $net_amount < 0)
            $net_amount = 0;

        $gross_amount = $net_amount;

        $vatRate = 0;
        $vatRateRow = Dictionary::find($vat_rate_id);
        if($vatRateRow)
        {
            $vatRate = str_replace(",", "", $vatRateRow->value);
            if(!is_numeric($vatRate) || $vatRate < 0)
                $vatRate = 0;
        }

        if($vatRate)
            $gross_amount = $net_amount * ((100 + $vatRate) / 100);

        return $gross_amount;
    }

    // Tworzy wielopoziomową tablicę na podstawie kluczy rozdzielonych kropką
    public static function makeArray($key, &$array, $value)
    {
        if(strpos($key, ".") !== false)
        {
            $keys = explode(".", $key);
            foreach($keys as $i => $k)
                $array = &$array[$k];
        }
        else
            $array = &$array[$key];

        $array = $value;
    }

    public static function plurals($cnt, $f1, $f2, $f3) {
		if($cnt == 1) return $f1;

        $div1 = $cnt % 10;
        if($div1 <= 1 || $div1 >= 5) return $f3;

        $div2 = ($cnt-$div1)/10 % 10;
        if($div2 == 1) return $f3;

        return $f2;
	}

    public static function plulars($words, $i = 0) {
		$words = explode("|", $words);
		if($i == 0)
			return $words[2];
		if($i < 2)
			return $words[0];
		if($i < 5)
			return $words[1];
		return $words[2];
	}

    public static function __no_pl($tekst, $replaceExtraChar = true) {
		$tabela = Array(
		//WIN
		"\xb9" => "a", "\xa5" => "A", "\xe6" => "c", "\xc6" => "C", "\xea" => "e", "\xca" => "E", "\xb3" => "l", "\xa3" => "L", "\xf3" => "o", "\xd3" => "O", "\x9c" => "s", "\x8c" => "S", "\x9f" => "z", "\xaf" => "Z", "\xbf" => "z", "\xac" => "Z", "\xf1" => "n", "\xd1" => "N",
		//UTF
		"\xc4\x85" => "a", "\xc4\x84" => "A", "\xc4\x87" => "c", "\xc4\x86" => "C", "\xc4\x99" => "e", "\xc4\x98" => "E", "\xc5\x82" => "l", "\xc5\x81" => "L", "\xc3\xb3" => "o", "\xc3\x93" => "O", "\xc5\x9b" => "s", "\xc5\x9a" => "S", "\xc5\xbc" => "z", "\xc5\xbb" => "Z", "\xc5\xba" => "z", "\xc5\xb9" => "Z", "\xc5\x84" => "n", "\xc5\x83" => "N",
		//ISO
		"\xb1" => "a", "\xa1" => "A", "\xe6" => "c", "\xc6" => "C", "\xea" => "e", "\xca" => "E", "\xb3" => "l", "\xa3" => "L", "\xf3" => "o", "\xd3" => "O", "\xb6" => "s", "\xa6" => "S", "\xbc" => "z", "\xac" => "Z", "\xbf" => "z", "\xaf" => "Z", "\xf1" => "n", "\xd1" => "N");

		if($replaceExtraChar) {
			$tekst = str_replace(array(" ", "?", "/", "\\"), array("-"), $tekst);
			$tekst = str_replace(array("'", "\"", "#", "&"), array(""), $tekst);
			return strtolower(strtr(self::CyrilicToLatin($tekst), $tabela));
		}
		return strtr(self::CyrilicToLatin($tekst), $tabela);
	}

    public static function CyrilicToLatin($textcyr) {
        $cyr  = array('а','б','в','г','д','e','ж','з','и','й','к','л','м','н','о','п','р','с','т','у',
          'ф','х','ц','ч','ш','щ','ъ','ь', 'ю','я','А','Б','В','Г','Д','Е','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У',
          'Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ь', 'Ю','Я' );
        $lat = array( 'a','b','v','g','d','e','zh','z','i','y','k','l','m','n','o','p','r','s','t','u',
          'f' ,'h' ,'ts' ,'ch','sh' ,'sht' ,'a' ,'y' ,'yu' ,'ya','A','B','V','G','D','E','Zh',
          'Z','I','Y','K','L','M','N','O','P','R','S','T','U',
          'F' ,'H' ,'Ts' ,'Ch','Sh' ,'Sht' ,'A' ,'Y' ,'Yu' ,'Ya' );
        return str_replace($cyr, $lat, $textcyr);
    }

    public static function slownie($x, $currency = "zł") {
        if($x < 0) $x = -1 * $x;
        $ss = array("","sto ","dwieście ","trzysta ","czterysta ","pięcset ","sześćset ","siedemset ","osiemset ","dziewięćset ");
        $dd = array("","dziesięć ","dwadzieścia ","trzydzieści ","czterdzieści ","pięćdziesiąt ","sześćdziesiąt ","siedemdziesiąt ","osiemdziesiąt ","dziewięćdziesiąt ");
        $jj = array("","jeden ","dwa ","trzy ","cztery ","pięć ","sześć ","siedem ","osiem ","dziewięć ","dziesięć ","jedenaście ","dwanaście ","trzynaście ","czternaście ","piętnaście ","szesnaście ","siedemnaście ","osiemnaście ","dziewiętnaście ");

        $x = number_format($x, 2, ".", "");

        $buf = explode(",",str_replace(".",",",$x));

        $w = "";

        if($buf[0]>=1000000) {
            $l = (int)($buf[0]/1000000);
            $w .= $ss[$l/100];

            if($l == 1) $k = 'milon ';
            elseif($l%10>1 && $l%10<5 && ($l<10 || $l>20)) $k = 'miliony ';
            else $k = 'milonów ';

            $l = $l - 100*(int)($l/100);

            if((int)$l<20)
                $w .= $jj[$l];
            else
                $w .= $dd[substr($l,0,1)].$jj[substr($l,1,1)];
            $w .= $k;
        }

        $buf[0] = $buf[0] - 1000000*(int)($buf[0]/1000000);

        if($buf[0]>=1000) {
            $l = (int)($buf[0]/1000);
            $w .= $ss[$l/100];

            if($l == 1) $k = 'tysiąc ';
            elseif($l%10>1 && $l%10<5 && ($l<10 || $l>20)) $k = 'tysiące ';
            else $k = 'tysięcy ';

            $l = $l - 100*(int)($l/100);

            if((int)$l<20)
                $w .= $jj[$l];
            else
                $w .= $dd[substr($l,0,1)].$jj[substr($l,1,1)];
            $w .= $k;
        }

        $buf[0] = $buf[0] - 1000*(int)($buf[0]/1000);

        if($buf[0]>=1) {
            $l = $buf[0];

            $w .= $ss[$l/100];

            $l = $l - 100*(int)($l/100);

            if((int)$l<20)
                $w .= $jj[$l];
            else
                $w .= $dd[substr($l,0,1)].$jj[substr($l,1,1)];
        }
        $w .= ' ' . $currency . ' ';
        if($buf[1] > 0) $w .= ($w ? "i " : "").$buf[1]."/100";
        else $w .= "i zero";

        return $w;
    }

    public static function setLang($lang)
    {
        $availableLang = config("panel.locale");
        if(empty($availableLang[$lang]))
            $lang = "pl";
        Cookie::queue("lang", $lang);
    }

    public static function getLang()
    {
        return Cookie::get("lang", "pl");
    }

    public static function getCacheKey($type)
    {
        $uuid = \Auth::user()->getAccountUuid();
        return $uuid . ":" . $type;
    }

    public static function editableItemCurrentVal($values, $item)
    {
        if(empty($item["product_name"]))
            return $values;

        $item["name"] = $item["product_name"];
        $item["price"] = $item["net_amount"];
        $item["gross_price"] = $item["gross_amount"];
        $item["selected"] = true;
        $values = array_merge([$item], $values);

        return $values;
    }

    private static $notificationList = [
        "all" => false,
        "unread" => false,
    ];
    public static function getUserNotifyList($onlyUnread = true)
    {
        $arrayCacheKey = $onlyUnread ? "unread" : "all";
        $limit = $onlyUnread ? 5 : 10;

        if(static::$notificationList[$arrayCacheKey] !== false)
            return static::$notificationList[$arrayCacheKey];

        $notificationList = null;
        if($onlyUnread)
            $notifications = \App\Models\NotificationUsers::where("message_read", 0)->get();
        else
            $notifications = \App\Models\NotificationUsers::get();

        if(!$notifications->isEmpty())
        {
            $ids = [];
            foreach($notifications as $row)
                $ids[] = $row->notification_id;

            $notificationList = \App\Models\Notifications::whereIn("id", $ids)->orderBy("created_at", "DESC")->paginate($limit);
        }

        static::$notificationList[$arrayCacheKey] = $notificationList;
        return $notificationList;
    }

    public static function userShowMarquee()
    {
        $settings = \App\Models\UserSettings::getUserSettings(Auth::user()->id);
        if(($settings["notification_type"] ?? "") == "animation")
        {
            $notify = self::getUserNotifyList();
            return $notify !== null && !$notify->isEmpty();
        }
        return false;
    }

    public static function userHasUnreadNotify()
    {
        return \App\Models\NotificationUsers::where("message_read", 0)->count() > 0;
    }

    public static function calculateWorkingDays($d1, $d2)
    {
        $objStartDate = new \DateTime($d1);
        $objEndDate = new \DateTime($d2);
        $interval = new \DateInterval("P1D");
        $dateRange = new \DatePeriod($objStartDate, $interval, $objEndDate);

        $count = 0;
        foreach ($dateRange as $eachDate) {
            if($eachDate->format("w") != 6 && $eachDate->format("w") != 0)
            {
                if(!\App\Libraries\Holiday::isHoliday($eachDate))
                    $count++;
            }
        }
        return $count;
    }

    public static function getNextWorkingDay($d1)
    {
        $date = new \DateTime($d1);
        $date->add(new \DateInterval("P1D"));
        if($date->format("w") == 6 || $date->format("w") == 0 || \App\Libraries\Holiday::isHoliday($date))
            return self::getNextWorkingDay($date->format("Y-m-d"));
        return $date->format("Y-m-d");
    }

    public static function dateDiff($d1, $d2)
    {
        $objStartDate = new \DateTime($d1);
        $objEndDate = new \DateTime($d2);

        return $objEndDate->diff($objStartDate)->format("%a");
    }
}
