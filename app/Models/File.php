<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DbTimestamp;

class File extends Model
{
    use DbTimestamp;
    use \App\Traits\UuidTrait {
        boot as traitBoot;
    }
    
    const PROJECT = "project";
    const TASK = "task";
    const COMMENT = "comment";
    
    public function scopeApiFields(Builder $query): void
    {
        $query->select("id", "user_id", "type", "filename", "orig_name", "extension", "size", "description", "created_at");
    }
    
    public function delete()
    {
        $directory = self::getUploadDirectory($this->type, true, $this->uuid);
        if(file_exists($directory . "/" . $this->filename))
            unlink($directory . "/" . $this->filename);
        
        return parent::delete();
    }

    public static function getUploadDirectory($type, $absolute = true, $uuid = null)
	{
        if($uuid === null)
            $uuid = Auth::user()->getUuid();
        $path = "upload/files/" . $uuid . "/" . $type;
        
		$directory = storage_path($path);
		@mkdir($directory, 0777, true);
        
        if(!$absolute)
            return $path;
        
        return $directory;
	}
    
    public function fileExists()
    {
        $directory = self::getUploadDirectory($this->type);
        return file_exists($directory . "/" . $this->filename);
    }
    
    public function getBase64()
    {
        $base64 = "";
        if($this->fileExists())
        {
            $directory = self::getUploadDirectory($this->type);
            
            $fp = fopen($directory . "/" . $this->filename, "r");
            $content = fread($fp, filesize($directory . "/" . $this->filename));
            fclose($fp);
            
            $base64 = base64_encode($content);
        }
        return $base64;
    }
}