<?php

namespace App\Exceptions;

use Exception;

class AccountNotActivatedException extends Exception
{
    public function __construct(string $message = '帳號尚未開通')
    {
        parent::__construct($message);
    }
}
