<?php

namespace App\Exceptions;

use Exception;

class ObjectNotExist extends Exception
{
    public $status = 404;
    
    public function render($request)
    {
        if($request->expectsJson())
            return $this->handleAjax();

        return redirect()->back()->withInput()->withErrors($this->getMessage());
    }
    
    private function handleAjax()
    {
        return response()->json([
            'error'   => true,
            'message' => $this->getMessage(),
        ], $this->status);
    }
    
    public function withStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}