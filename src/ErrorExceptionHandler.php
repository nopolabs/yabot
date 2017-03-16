<?php

namespace Nopolabs\Yabot;

use ErrorException;

class ErrorExceptionHandler
{
    public static function handler($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
}