<?php

namespace App\Exceptions;

use Exception;

class StudentLoginClosedException extends Exception
{
    public function __construct(string $message = '學生登入已關閉')
    {
        parent::__construct($message);
    }
}
