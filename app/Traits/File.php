<?php

namespace App\Traits;

use Exception;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\File as FileModel;

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
            $attachment = json_decode($attachment);
            try
            {
                DB::transaction(function() use($attachment, $type, &$sort) {
                    $directory = FileModel::getUploadDirectory($type);
                    
                    $f = finfo_open();
                    $mime = finfo_buffer($f, base64_decode($attachment->base64), FILEINFO_MIME_TYPE);
                    if(!empty(config("api.upload.allowed_mime_types")[$mime]))
                        $extension = config("api.upload.allowed_mime_types")[$mime];
                        
                    if(!$extension)
                        throw new Exception(__("Unsupported file type"));
                    
                    $filename = bin2hex(openssl_random_pseudo_bytes(16)) . "." . $extension;
                    
                    $fp = fopen($directory . "/" . $filename, "w");
                    fwrite($fp, base64_decode($attachment->base64));
                    fclose($fp);
                    
                    $size = filesize($directory . "/" . $filename);
                    if($size > 0)
                    {
                        $row = new FileModel;
                        $row->type = $type;
                        $row->object_id = $this->id;
                        $row->user_id = Auth::user()->id;
                        $row->filename = $filename;
                        $row->orig_name = $attachment->name;
                        $row->extension = $extension;
                        $row->size = $size;
                        $row->description = $attachment->description ?? "";
                        $row->sort = ++$sort;
                        $row->save();
                    }
                });
            }
            catch(Throwable $e)
            {
                static::$errors[] = $e->getMessage();
            }
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

    public function getAttachments($type = null)
    {
        if($type === null) $type = $this->getType();
        return FileModel::apiFields()->where("type", $type)->where("object_id", $this->id)->orderBy("sort", "ASC")->get();
    }
}
