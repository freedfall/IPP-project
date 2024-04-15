<?php
/**
 * IPP Interpreter
 * Class for handling value exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class ValueException extends IPPException
{
    public function __construct(string $message = "ERROR - missing value")
    {
        parent::__construct($message, ReturnCode::VALUE_ERROR);
    }
}