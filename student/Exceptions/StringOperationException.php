<?php
/**
 * IPP Interpreter
 * Class for handling string operation exception
 * @author Timur Kininbayev (xkinin00)
 */

namespace IPP\Student\Exceptions;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class StringOperationException extends IPPException
{
    public function __construct(string $message = "ERRROR - incorrect work with the string")
    {
        parent::__construct($message, ReturnCode::STRING_OPERATION_ERROR);
    }
}