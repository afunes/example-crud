<?php

namespace App\Models;

class ApiError
{
    public $isError;
    public $message;
    public $exist;


    public function __construct(){
        $this->isError  = false;
        $this->exist    = false;
        $this->code     = 200;
        $this->message  = '';
        $this->data     = [];
    }

    protected $casts = [
        'isError'   => 'boolean',
        'exist'     => 'boolean',
        'id'        => 'integer',
        'message'   => 'string'
    ];

    public function toJSON(){
        return json_encode($this);
    }

    public function setError($sMessage, $code = 400){
        $this->isError  =  true;
        $this->code     =  $code;
        $this->message  =  $sMessage;
    }

    public function setErrors(array $errors, $code = 400){
        $this->isError  =  true;
        $this->code     =  $code;
        $this->errors   =  $errors;
    }

    public function setCode($code) {
        $this->code     =  $code;
    }

    public function setMessage($sMessage){
        $this->message  =  $sMessage;
    }

}
