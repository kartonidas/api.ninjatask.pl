<?php

namespace App\Traits;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Limit;
use App\Models\File as FileModel;
use App\Models\Subscription;

trait File
{
    private static $errors = [];
    
    public function getType()
    {
        return $this->getTable();
    }
    
    public function upload($attachments, $type = null)
    {
        if($type === null) $type = $this->getType();
            
        $sort = FileModel::where("type", $type)->where("object_id", $this->id)->max("sort");
        foreach($attachments as $attachment)
        {
            try
            {
                DB::transaction(function() use($attachment, $type, &$sort) {
                    $sort++;
                    $this->uploadSingle($attachment, $type, $sort);
                });
            }
            catch(Throwable $e)
            {
                static::$errors[] = $e->getMessage();
            }
        }
    }
    
    public function uploadSingle($attachment, $type = null, $sort = null)
    {
        if($type === null) $type = $this->getType();
        if($sort === null)
        {
            $sort = FileModel::where("type", $type)->where("object_id", $this->id)->max("sort");
            $sort += 1;
        }
        
        $directory = FileModel::getUploadDirectory($type);
                    
        if(!empty($attachment["file"]) && $attachment["file"] instanceof \Illuminate\Http\UploadedFile)
        {
            self::checkSpaceLimit($attachment["file"]->getSize());
            
            $relativeDirectory = FileModel::getUploadDirectory($type, false);
            $mime = $attachment["file"]->getMimeType();
            if(!empty(config("api.upload.allowed_mime_types")[$mime]))
                $extension = config("api.upload.allowed_mime_types")[$mime];
                
            if(empty($extension))
                throw new Exception(__("Unsupported file type"));
            
            $attachment["name"] = $attachment["file"]->getClientOriginalName();
            $filename = bin2hex(openssl_random_pseudo_bytes(16)) . "." . $extension;
            $path = $attachment["file"]->storeAs($relativeDirectory, $filename, "upload");
        }
        else
        {
            self::checkSpaceLimit((int)(strlen(base64_decode($attachment["base64"])) * 0.75));
            
            $f = finfo_open();
            $mime = finfo_buffer($f, base64_decode($attachment["base64"]), FILEINFO_MIME_TYPE);
            if(!empty(config("api.upload.allowed_mime_types")[$mime]))
                $extension = config("api.upload.allowed_mime_types")[$mime];
                
            if(empty($extension))
                throw new Exception(__("Unsupported file type"));
            
            $filename = bin2hex(openssl_random_pseudo_bytes(16)) . "." . $extension;
            
            $fp = fopen($directory . "/" . $filename, "w");
            fwrite($fp, base64_decode($attachment["base64"]));
            fclose($fp);
        }
        
        $size = filesize($directory . "/" . $filename);
        if($size > 0)
        {
            $row = new FileModel;
            $row->type = $type;
            $row->object_id = $this->id;
            $row->user_id = Auth::user()->id;
            $row->filename = $filename;
            $row->orig_name = $attachment["name"];
            $row->extension = $extension;
            $row->size = $size;
            $row->description = $attachment["description"] ?? "";
            $row->sort = $sort;
            $row->save();
            
            return $row->id;
        }
    }

    public function getUploadErrors()
    {
        return array_unique(static::$errors);
    }

    public function removeFiles($toRemove = [], $type = null)
    {
        if(!$toRemove)
            return;

        if($type === null) $type = $this->getType();

        foreach($toRemove as $id)
        {
            $row = FileModel::where("id", $id)->where("type", $type)->where("object_id", $this->id)->first();
            if($row)
                $row->delete();
        }
    }

    public function getAttachments($type = null, $withoutUuidScope = false)
    {
        if($type === null) $type = $this->getType();
        
        $files = FileModel::where("type", $type)->where("object_id", $this->id)->orderBy("created_at", "DESC");
        if($withoutUuidScope)
            $files->withoutGlobalscopes();
        else
            $files->apiFields();
    
        return $files->get();
    }
    
    public function getAttachmentsToDeleted($type = null, $withoutUuidScope = false)
    {
        if($type === null) $type = $this->getType();
        
        $files = FileModel::where("type", $type)->where("object_id", $this->id)->orderBy("created_at", "DESC");
        if($withoutUuidScope)
            $files->withoutGlobalscopes();
    
        return $files->get();
    }
    
    public function attachBase64File($attachments = [], $allowedExtensions = [])
    {
        if(!empty($attachments))
        {
            foreach($attachments as $k => $attachment)
            {
                if(empty($allowedExtensions) || in_array($attachment->extension, $allowedExtensions))
                    $attachments[$k]->base64 = $attachment->getBase64();
            }
        }
        return $attachments;
    }
    
    private static function checkSpaceLimit($size)
    {
        $subscription = Subscription::where("status", Subscription::STATUS_ACTIVE)->first();
        if(!$subscription)
            throw new Exception(__("Out of disk space"));
        
        $current = Limit::first();
        $limits = $subscription->free ? config("packages.free") : config("packages.paid");
        
        if($current && $current->space + $size > $limits["space"])
            throw new Exception(__("Out of disk space"));
    }
}
