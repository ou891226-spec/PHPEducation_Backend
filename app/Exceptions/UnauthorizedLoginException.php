<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedLoginException extends Exception
{
    public function __construct(string $message = '帳號或密碼錯誤')
    {
        parent::__construct($message);
    }
}
